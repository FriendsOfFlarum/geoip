import { jest, describe, it, expect, beforeEach } from '@jest/globals';
import m from 'mithril';

/**
 * Exercises the real core IPAddress component with the extension's extenders
 * applied, through the real Mithril lifecycle (oninit -> view -> oncreate).
 *
 * This is the regression net for issue #111: repeatedly rendering the same IP
 * (the audit log's "Load more", which mounts fresh components for rows already
 * on screen) must not re-request it, and a row must end up showing the
 * extension's enriched output rather than the bare IP.
 *
 * jsdom has no IntersectionObserver, and the component only triggers a lookup
 * from one, so the tests install a stub.
 *
 * Two stubs exist, and the distinction matters. The "immediate" one treats
 * every element as visible, which is convenient for asserting caching
 * behaviour. The "viewport" one honours each element's position and only
 * reports an intersection once the element is scrolled into range — which is
 * what a real browser does, and what the audit log's below-the-fold rows hit.
 * An always-visible stub hides that entire class of bug (issue #111), so the
 * observer's own behaviour is tested against the viewport-aware one.
 */

let requestedIds: string[] = [];

/** Number of repaints Mithril was *asked* for via `m.redraw()`. */
let redrawRequests = 0;

/**
 * Drain pending promises, then perform a repaint only if one was requested.
 *
 * Deliberately not a bare `m.redraw.sync()`: forcing a repaint would make a
 * component that never calls `m.redraw()` look correct, which is exactly how
 * the missing-redraw defect in #111 stayed hidden. Here, a row only repaints
 * if its own code asked for it.
 */
async function settle() {
  await new Promise((resolve) => setTimeout(resolve, 0));

  if (redrawRequests > 0) {
    redrawRequests = 0;
    m.redraw.sync();
  }
}

function installIntersectionObserver() {
  // Fires the callback synchronously on observe(), i.e. always "in viewport".
  class ImmediateIntersectionObserver {
    constructor(private callback: (entries: { isIntersecting: boolean }[]) => void) {}
    observe() {
      this.callback([{ isIntersecting: true }]);
    }
    disconnect() {}
    unobserve() {}
  }

  (globalThis as any).IntersectionObserver = ImmediateIntersectionObserver;
}

/**
 * A stub that models a real viewport: an element only intersects once its
 * recorded offset falls within `viewportHeight` (plus the observer's
 * rootMargin). `scrollTo` re-evaluates every observed element, as a browser
 * does on scroll.
 */
class FakeViewport {
  private observers: { el: Element; cb: (entries: { isIntersecting: boolean; target: Element }[]) => void; margin: number; disconnected: boolean }[] =
    [];
  private offsets = new Map<Element, number>();

  constructor(
    public viewportHeight = 500,
    public scrollY = 0
  ) {}

  /** Place an element at a fixed document offset. */
  place(el: Element, top: number) {
    this.offsets.set(el, top);
  }

  private intersects(el: Element, margin: number) {
    const top = this.offsets.get(el) ?? 0;

    return top >= this.scrollY - margin && top <= this.scrollY + this.viewportHeight + margin;
  }

  install() {
    const viewport = this;

    class ViewportIntersectionObserver {
      private margin: number;

      constructor(
        private callback: (entries: { isIntersecting: boolean; target: Element }[]) => void,
        options?: { rootMargin?: string }
      ) {
        this.margin = parseInt(options?.rootMargin ?? '0', 10) || 0;
      }

      observe(el: Element) {
        const entry = { el, cb: this.callback, margin: this.margin, disconnected: false };
        viewport.observers.push(entry);

        // Defer the first check by a tick: a real observer never fires
        // synchronously from observe(), and the element's position may not be
        // registered until the caller has finished laying the row out.
        queueMicrotask(() => {
          if (entry.disconnected) return;

          if (viewport.intersects(el, this.margin)) {
            this.callback([{ isIntersecting: true, target: el }]);
          }
        });
      }

      disconnect() {
        for (const o of viewport.observers) {
          if (o.cb === this.callback) o.disconnected = true;
        }
      }

      unobserve(el: Element) {
        for (const o of viewport.observers) {
          if (o.cb === this.callback && o.el === el) o.disconnected = true;
        }
      }
    }

    (globalThis as any).IntersectionObserver = ViewportIntersectionObserver;

    return this;
  }

  /** Scroll the viewport, firing intersections for anything newly in range. */
  scrollTo(y: number) {
    this.scrollY = y;

    for (const o of this.observers) {
      if (o.disconnected) continue;

      if (this.intersects(o.el, o.margin)) {
        o.cb([{ isIntersecting: true, target: o.el }]);
      }
    }
  }
}

async function boot() {
  // Core's Drawer attaches a listener to #content during bootstrap.
  document.body.innerHTML = '<div id="app"><div id="content"></div></div>';

  const { default: bootstrapForum } = await import('@flarum/jest-config/src/bootstrap/forum');

  bootstrapForum({
    resources: [
      { type: 'forums', id: '1', attributes: { fofGeoipCanSeeIpInfo: true } },
      { type: 'users', id: '1', attributes: { id: 1, username: 'admin', displayName: 'Admin' } },
    ],
  });

  // The extension extends `IPAddress` via its lazy module path, which resolves
  // through `flarum.reg`. The jest bootstrap does not populate the registry
  // (webpack does that in a real build), so register core's component before
  // importing the extension — `onLoad` then applies the extenders immediately.
  const { default: IPAddress } = await import('flarum/common/components/IPAddress');
  flarum.reg.add('core', 'common/components/IPAddress', IPAddress);

  await import('../../src/forum/index');

  app.boot();

  // The `ip_info` model is registered through the extension's `Extend.Store`
  // extender, which the real frontend applies from the compiled `extend`
  // export. `app.boot()` does not run extenders, so register it here.
  const { default: IPInfo } = await import('../../src/common/model/IPInfo');
  app.store.models.ip_info = IPInfo;

  // Count redraw requests without suppressing them, so `settle()` can tell a
  // component that asked for a repaint from one that silently did not.
  redrawRequests = 0;
  const realRedraw = m.redraw;
  if (!(m.redraw as any).__counted) {
    const counted: any = () => {
      redrawRequests++;
      return realRedraw();
    };
    counted.sync = realRedraw.sync.bind(realRedraw);
    counted.__counted = true;
    (m as any).redraw = counted;
  }

  // Stub the store lookup so no network is involved and every call is counted.
  // `pushObject` builds a real IPInfo via the model the extension registered.
  requestedIds = [];
  app.store.find = ((type: string, id: string) => {
    requestedIds.push(id);

    return Promise.resolve(
      app.store.pushObject({
        // The id the backend returns is a hash of the address, never the
        // address itself.
        id: `hash-of-${id}`,
        type: 'ip_info',
        // `ip` is deliberately omitted: it is permission-gated server-side and
        // absent for actors without discussion.viewIpsPosts. The cache must not
        // depend on it.
        attributes: { countryCode: 'DE' },
      })
    );
  }) as typeof app.store.find;
}

/**
 * Mount a fresh IPAddress component for each IP, as a "Load more" batch would.
 * Returns the rendered root nodes so their markup can be inspected.
 */
async function renderBatch(ips: string[]): Promise<HTMLElement[]> {
  const { default: IPAddress } = await import('flarum/common/components/IPAddress');

  const roots = ips.map((ip) => {
    const root = document.createElement('div');
    document.body.appendChild(root);
    m.mount(root, { view: () => m(IPAddress as any, { ip }) });
    return root;
  });

  // Let the lookup promises settle and Mithril repaint.
  await new Promise((resolve) => setTimeout(resolve, 0));
  m.redraw.sync();

  return roots;
}

describe('IPAddress lookups', () => {
  beforeEach(async () => {
    jest.resetModules();
    document.body.innerHTML = '';
    installIntersectionObserver();
    await boot();
  });

  it('requests each unique IP only once within a batch', async () => {
    await renderBatch(['1.1.1.1', '8.8.8.8', '1.1.1.1', '8.8.8.8']);

    expect(requestedIds).toHaveLength(2);
  });

  /**
   * Real audit logs are dominated by IPv6 addresses, whose colons are
   * percent-encoded on the way out. The cache key must be the address itself,
   * not the encoded form or anything derived from the response.
   */
  it('caches IPv6 addresses across batches', async () => {
    const ipv6 = ['2a02:390:9e5c:beef:dd5f:971f:e466:80c0', '2a02:390:9e5c:beef:9cff:1ffc:20b7:da00'];

    await renderBatch(ipv6);
    await renderBatch(ipv6);
    const [root] = await renderBatch([ipv6[0]]);

    expect(requestedIds).toHaveLength(2);
    expect(root.querySelector('.ip-info')).not.toBeNull();
  });

  /**
   * Audit rows for CLI actions carry no IP at all. Core renders IPAddress with
   * an empty string, which must never produce a lookup.
   */
  it('never requests anything for rows without an IP', async () => {
    await renderBatch(['', '', '']);

    expect(requestedIds).toHaveLength(0);
  });

  /**
   * ip-api answers private and reserved ranges (and some residential IPv6)
   * with `status: fail` and a `message` such as "private range". IPApi treats
   * those as non-errors and returns a record carrying only isp/organization —
   * `countryCode` is never set. The row is still enriched (copy and info
   * buttons, tooltip), but there is no flag to show.
   *
   * Guards the null-country path end to end: no crash, no repeated lookup, and
   * the row is not left as the bare IP.
   */
  it('handles a record with no countryCode', async () => {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    app.store.find = ((type: string, id: string) => {
      requestedIds.push(id);

      return Promise.resolve(
        app.store.pushObject({
          id: `hash-of-${id}`,
          type: 'ip_info',
          // What a "private range" lookup actually yields.
          attributes: { countryCode: null, isp: 'private range', organization: 'private range' },
        })
      );
    }) as typeof app.store.find;

    const root = document.createElement('div');
    document.body.appendChild(root);
    m.mount(root, { view: () => m(IPAddress as any, { ip: '192.168.65.1' }) });

    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    // Enriched (so the extension did render), but with no flag image.
    expect(root.querySelector('.ip-info')).not.toBeNull();
    expect(root.querySelector('.ip-info img')).toBeNull();
    expect(requestedIds).toHaveLength(1);
  });

  it('does not re-request IPs when a later batch renders them again', async () => {
    await renderBatch(['1.1.1.1', '8.8.8.8']);
    const afterFirst = requestedIds.length;

    // "Load more": fresh components for the same addresses.
    await renderBatch(['1.1.1.1', '8.8.8.8']);
    await renderBatch(['1.1.1.1', '8.8.8.8']);

    expect(afterFirst).toBe(2);
    expect(requestedIds).toHaveLength(2);
  });

  it('enriches a row rendered in a later batch', async () => {
    await renderBatch(['1.1.1.1']);

    const [root] = await renderBatch(['1.1.1.1']);

    // The enriched markup replaces the plain `IPAddress-value` span.
    expect(root.querySelector('.ip-info')).not.toBeNull();
    expect(root.querySelector('.IPAddress-value')).toBeNull();
  });

  it('enriches a row on first render too', async () => {
    const [root] = await renderBatch(['9.9.9.9']);

    expect(root.querySelector('.ip-info')).not.toBeNull();
  });

  /**
   * The audit log's "Load more" appends rows to a list that is already
   * mounted, rather than mounting a fresh root per batch. A row whose IP was
   * resolved by an earlier batch is then created inside an existing redraw
   * cycle: `oninit` reads the cache, but if the lookup path returns early
   * without asking for a repaint, nothing re-renders that row and it stays as
   * the bare IP. This is the "not augmented after Load more" report in #111.
   */
  it('enriches an appended row whose IP was resolved by an earlier batch', async () => {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    const ip = '2a02:390:9e5c:beef:dd5f:971f:e466:80c0';
    const rows: string[] = [ip];

    // One mounted list that grows, as the audit browser does.
    const root = document.createElement('div');
    document.body.appendChild(root);
    m.mount(root, {
      view: () =>
        m(
          'div',
          rows.map((r, i) => m(IPAddress as any, { key: i, ip: r }))
        ),
    });

    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    expect(requestedIds).toHaveLength(1);
    expect(root.querySelectorAll('.ip-info')).toHaveLength(1);

    // "Load more" appends another row with the same (already resolved) IP.
    rows.push(ip);
    m.redraw.sync();
    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    expect(requestedIds).toHaveLength(1);
    expect(root.querySelectorAll('.ip-info')).toHaveLength(2);
  });

  /**
   * A row mounted while the lookup for its IP is still in flight must still
   * end up enriched. This is the case a cache that only redraws on the
   * *initiating* component would miss: the second component adopts the shared
   * promise, and if it returns without asking Mithril to repaint, the row
   * stays as the bare IP until some unrelated redraw happens to occur.
   */
  it('enriches a row that mounts while the lookup is still in flight', async () => {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    let release: (value: unknown) => void = () => {};
    const pending = new Promise((resolve) => {
      release = resolve;
    });

    app.store.find = ((type: string, id: string) => {
      requestedIds.push(id);

      return pending.then(() => app.store.pushObject({ id: `hash-of-${id}`, type: 'ip_info', attributes: { countryCode: 'DE' } }));
    }) as typeof app.store.find;

    const mountRow = () => {
      const root = document.createElement('div');
      document.body.appendChild(root);
      m.mount(root, { view: () => m(IPAddress as any, { ip: '4.4.4.4' }) });
      return root;
    };

    // First row starts the lookup; second mounts while it is still pending.
    const first = mountRow();
    const second = mountRow();

    release(null);
    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    expect(requestedIds.filter((id) => id === '4.4.4.4')).toHaveLength(1);
    expect(first.querySelector('.ip-info')).not.toBeNull();
    expect(second.querySelector('.ip-info')).not.toBeNull();
  });
});

/**
 * Regression tests for the root cause of #111, using a viewport-aware observer.
 *
 * The audit log appends rows far below the fold on "Load more". Those rows are
 * created outside the viewport, so a lookup that only ever fires from an
 * intersection must still happen once the user scrolls down to them — and the
 * row must then enrich.
 */
describe('IPAddress lookups below the fold', () => {
  let viewport: FakeViewport;

  beforeEach(async () => {
    jest.resetModules();
    document.body.innerHTML = '';
    viewport = new FakeViewport(500, 0).install();
    await boot();
  });

  /**
   * Render `count` rows, placing each one `spacing` px down the document, so
   * only the first few fall inside the 500px viewport.
   */
  async function renderRows(ips: string[], spacing = 400): Promise<HTMLElement[]> {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    // The element's position must be known before `oncreate` observes it,
    // otherwise every row looks like it is at offset 0 (i.e. always visible) —
    // exactly the blind spot that let this bug through. Pre-register the
    // offset against the DOM node Mithril is about to create.
    const roots = ips.map((ip, i) => {
      const root = document.createElement('div');
      document.body.appendChild(root);

      const span = document.createElement('span');
      root.appendChild(span);
      viewport.place(span, i * spacing);

      m.mount(root, { view: () => m(IPAddress as any, { ip }) });

      // Mithril replaces the placeholder; carry the offset onto the real node.
      if (root.firstElementChild && root.firstElementChild !== span) {
        viewport.place(root.firstElementChild, i * spacing);
      }

      return root;
    });

    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    return roots;
  }

  it('does not request IPs for rows created below the viewport', async () => {
    // 6 rows at 400px apart: only the first two are within 500px.
    await renderRows(['1.1.1.1', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5', '6.6.6.6']);

    expect(requestedIds.length).toBeLessThan(6);
  });

  it('requests and enriches a below-the-fold row once it is scrolled into view', async () => {
    const ips = ['1.1.1.1', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5', '6.6.6.6'];
    const roots = await renderRows(ips);

    const lastRow = roots[roots.length - 1];
    expect(lastRow.querySelector('.ip-info')).toBeNull();

    // Scroll down to the last row, as a user reading the log would.
    viewport.scrollTo(5 * 400);
    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    expect(requestedIds).toContain('6.6.6.6');
    expect(lastRow.querySelector('.ip-info')).not.toBeNull();
    expect(lastRow.querySelector('.ip-info img')).not.toBeNull();
  });

  it('enriches every row after scrolling through the whole list', async () => {
    const ips = ['1.1.1.1', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5', '6.6.6.6'];
    const roots = await renderRows(ips);

    for (let y = 0; y <= 5 * 400; y += 200) {
      viewport.scrollTo(y);
      await new Promise((resolve) => setTimeout(resolve, 0));
    }
    m.redraw.sync();

    for (const root of roots) {
      expect(root.querySelector('.ip-info')).not.toBeNull();
    }

    // Still exactly one request per unique IP.
    expect(requestedIds).toHaveLength(ips.length);
  });

  /**
   * The decisive case, observed live on the audit page: several rows share one
   * IP. The first resolves it and redraws; the rest scroll into view later,
   * find the entry already resolved, and must still repaint. If the cache-hit
   * path returns without requesting a redraw, those rows stay as the bare IP
   * forever even though the data is in hand.
   *
   * No explicit `m.redraw.sync()` here — that would paper over precisely the
   * missing redraw this test exists to catch.
   */
  it('enriches later rows sharing an IP that an earlier row already resolved', async () => {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    const ip = '2a02:390:9e5c:beef:dd5f:971f:e466:80c0';
    const roots: HTMLElement[] = [];

    for (let i = 0; i < 5; i++) {
      const root = document.createElement('div');
      document.body.appendChild(root);

      const placeholder = document.createElement('span');
      root.appendChild(placeholder);
      viewport.place(placeholder, i * 400);

      m.mount(root, { view: () => m(IPAddress as any, { ip }) });

      if (root.firstElementChild && root.firstElementChild !== placeholder) {
        viewport.place(root.firstElementChild, i * 400);
      }

      roots.push(root);
    }

    // Let the first (visible) row resolve the lookup.
    await settle();

    // Now scroll through the rest. Each finds the entry already resolved.
    // `settle()` drains promises and then runs whatever redraw was *requested*
    // — it never forces one, so a component that fails to ask for a repaint
    // stays un-enriched and the assertions below catch it.
    for (let y = 0; y <= 4 * 400; y += 400) {
      viewport.scrollTo(y);
      await settle();
    }

    expect(requestedIds).toHaveLength(1);

    for (const root of roots) {
      expect(root.querySelector('.ip-info')).not.toBeNull();
      expect(root.querySelector('.ip-info img')).not.toBeNull();
    }
  });

  it('does not re-request a row that scrolls in and out of view repeatedly', async () => {
    await renderRows(['1.1.1.1', '2.2.2.2', '3.3.3.3']);

    for (let i = 0; i < 4; i++) {
      viewport.scrollTo(800);
      await new Promise((resolve) => setTimeout(resolve, 0));
      viewport.scrollTo(0);
      await new Promise((resolve) => setTimeout(resolve, 0));
    }

    expect(new Set(requestedIds).size).toBe(requestedIds.length);
  });
});

/**
 * The backend eager loads ip_info and includes it with the posts response, so
 * by the time a post renders its IP the record is already in the store.
 *
 * Ignoring that and waiting for an IntersectionObserver to fire a fresh
 * request per row is both slow — the flag appears well after the post — and
 * wasteful, since it re-fetches data the browser already holds.
 */
describe('IPAddress records included with the page', () => {
  let viewport: FakeViewport;

  beforeEach(async () => {
    jest.resetModules();
    document.body.innerHTML = '';
    // Rows start below the fold, so nothing can be explained away by the
    // observer happening to fire.
    viewport = new FakeViewport(500, 0).install();
    await boot();
  });

  /** Push a record as the posts endpoint's `included` section would. */
  function includeRecord(ip: string, countryCode = 'DE') {
    return app.store.pushObject({
      // The backend's id is a hash of the address, never the address itself.
      id: `hash-of-${encodeURIComponent(ip)}`,
      type: 'ip_info',
      attributes: { ip, countryCode },
    });
  }

  async function mountRow(ip: string, offset = 2000) {
    const { default: IPAddress } = await import('flarum/common/components/IPAddress');

    const root = document.createElement('div');
    document.body.appendChild(root);

    const placeholder = document.createElement('span');
    root.appendChild(placeholder);
    viewport.place(placeholder, offset);

    m.mount(root, { view: () => m(IPAddress as any, { ip }) });

    if (root.firstElementChild && root.firstElementChild !== placeholder) {
      viewport.place(root.firstElementChild, offset);
    }

    await new Promise((resolve) => setTimeout(resolve, 0));
    m.redraw.sync();

    return root;
  }

  it('renders immediately from a record already in the store', async () => {
    includeRecord('8.8.8.8');

    const root = await mountRow('8.8.8.8');

    // Enriched on first paint, with no request and without being scrolled to.
    expect(root.querySelector('.ip-info')).not.toBeNull();
    expect(root.querySelector('.ip-info img')).not.toBeNull();
    expect(requestedIds).toHaveLength(0);
  });

  it('never requests an IP the page already supplied', async () => {
    includeRecord('8.8.8.8');
    includeRecord('1.1.1.1', 'AU');

    await mountRow('8.8.8.8');
    await mountRow('1.1.1.1');

    // Even once scrolled into view, there is nothing left to fetch.
    viewport.scrollTo(2000);
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(requestedIds).toHaveLength(0);
  });

  it('still fetches an IP the page did not supply', async () => {
    includeRecord('8.8.8.8');

    // Rendered in the viewport so the observer fires.
    await mountRow('9.9.9.9', 0);

    expect(requestedIds).toEqual(['9.9.9.9']);
  });

  /**
   * IPv6 addresses are percent-encoded in the resource id, so matching a
   * stored record to a component's plain address must survive that.
   */
  it('matches an included IPv6 record', async () => {
    const ip = '2a02:390:9e5c:beef:8899:30cb:242f:9539';
    includeRecord(ip, 'GB');

    const root = await mountRow(ip);

    expect(root.querySelector('.ip-info')).not.toBeNull();
    expect(requestedIds).toHaveLength(0);
  });
});
