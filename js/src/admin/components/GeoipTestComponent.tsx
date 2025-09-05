import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';

interface GeoipTestComponentAttrs {}

export default class GeoipTestComponent extends Component<GeoipTestComponentAttrs> {
  private testIP: string = '8.8.8.8';
  private testResult: any = null;
  private testLoading: boolean = false;
  private testError: string | null = null;

  view() {
    return (
      <div className="Section GeoipTest-testSection">
        <h3>{app.translator.trans('fof-geoip.admin.settings.test.heading')}</h3>
        <p className="helpText">{app.translator.trans('fof-geoip.admin.settings.test.help')}</p>
        {this.testItems().toArray()}
      </div>
    );
  }

  testItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'test-service',
      <div className="Form-group">
        <label>{app.translator.trans('fof-geoip.admin.settings.test_service_label')}</label>
        <div className="helpText">{app.translator.trans('fof-geoip.admin.settings.test_service_help')}</div>

        <div className="GeoipTest-inputGroup">
          <input
            type="text"
            className="FormControl"
            value={this.testIP}
            placeholder="8.8.8.8"
            onchange={(e: Event) => {
              this.testIP = (e.target as HTMLInputElement).value;
            }}
          />
          <button type="button" className="Button Button--primary" disabled={this.testLoading} onclick={this.testService.bind(this)}>
            {this.testLoading
              ? app.translator.trans('fof-geoip.admin.settings.testing')
              : app.translator.trans('fof-geoip.admin.settings.test_button')}
          </button>
        </div>

        {this.testError && <div className="Alert Alert--error GeoipTest-alert">{this.testError}</div>}

        {this.testResult && (
          <div className={`Alert ${this.testResult.success ? 'Alert--success' : 'Alert--error'} GeoipTest-alert`}>
            <h4>{app.translator.trans('fof-geoip.admin.settings.test_result_title')}</h4>

            <div className="GeoipTest-resultHeader">
              <strong>{app.translator.trans('fof-geoip.admin.settings.test_service_label_result')}</strong>{' '}
              {app.translator.trans(`fof-geoip.admin.settings.service_${this.testResult.service}_label`) || app.translator.trans('fof-geoip.admin.settings.status_unknown')}
              <br />
              <strong>{app.translator.trans('fof-geoip.admin.settings.test_status_label')}</strong>{' '}
              {this.testResult.success
                ? app.translator.trans('fof-geoip.admin.settings.status_success')
                : app.translator.trans('fof-geoip.admin.settings.status_error')}
              <br />
              {this.testResult.response_time_ms && (
                <span>
                  <strong>{app.translator.trans('fof-geoip.admin.settings.test_response_time_label')}</strong> {this.testResult.response_time_ms}ms
                  <br />
                </span>
              )}
              <strong>{app.translator.trans('fof-geoip.admin.settings.test_timestamp_label')}</strong> {this.testResult.timestamp}
            </div>

            <div>
              <h5>{app.translator.trans('fof-geoip.admin.settings.test_http_status_code_label')}</h5>
              <div className="GeoipTest-codeBlock">
                {this.testResult.http_status_code || app.translator.trans('fof-geoip.admin.settings.status_unknown')}
              </div>

              {!this.testResult.success && this.testResult.error && (
                <div>
                  <h5>{app.translator.trans('fof-geoip.admin.settings.test_error_details_label')}</h5>
                  <pre className="GeoipTest-codeBlock">
                    {app.translator.trans('fof-geoip.admin.settings.error_prefix')} {this.testResult.error}
                    {this.testResult.error_code &&
                      `\n${app.translator.trans('fof-geoip.admin.settings.error_code_prefix')} ${this.testResult.error_code}`}
                  </pre>
                </div>
              )}

              {this.testResult.response_headers && (
                <details className="GeoipTest-details">
                  <summary className="GeoipTest-detailsSummary">{app.translator.trans('fof-geoip.admin.settings.response_headers_label')}</summary>
                  <pre className="GeoipTest-codeBlock">{JSON.stringify(this.testResult.response_headers, null, 2)}</pre>
                </details>
              )}

              {this.testResult.raw_http_response && (
                <details className="GeoipTest-details">
                  <summary className="GeoipTest-detailsSummary">{app.translator.trans('fof-geoip.admin.settings.raw_http_response_label')}</summary>
                  <pre className="GeoipTest-codeBlock">
                    {(() => {
                      try {
                        // Try to parse and format the JSON for better readability
                        const parsed = JSON.parse(this.testResult.raw_http_response);
                        return JSON.stringify(parsed, null, 2);
                      } catch (e) {
                        // If it's not JSON, return as-is
                        return this.testResult.raw_http_response;
                      }
                    })()}
                  </pre>
                </details>
              )}

              {this.testResult.request_url && (
                <details className="GeoipTest-details">
                  <summary className="GeoipTest-detailsSummary">{app.translator.trans('fof-geoip.admin.settings.request_url_label')}</summary>
                  <pre className="GeoipTest-codeBlock">{this.testResult.request_url}</pre>
                </details>
              )}

              {this.testResult.request_options && (
                <details className="GeoipTest-details">
                  <summary className="GeoipTest-detailsSummary">{app.translator.trans('fof-geoip.admin.settings.request_options_label')}</summary>
                  <pre className="GeoipTest-codeBlock">{JSON.stringify(this.testResult.request_options, null, 2)}</pre>
                </details>
              )}

              {this.testResult.service_response && (
                <div>
                  <h5>{app.translator.trans('fof-geoip.admin.settings.test_processed_service_response_label')}</h5>
                  <pre className="GeoipTest-codeBlock">{JSON.stringify(this.testResult.service_response, null, 2)}</pre>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    );

    return items;
  }

  async testService() {
    if (!this.testIP.trim()) {
      this.testError = extractText(app.translator.trans('fof-geoip.admin.settings.test_ip_required'));
      m.redraw();
      return;
    }

    this.testLoading = true;
    this.testError = null;
    this.testResult = null;
    m.redraw();

    try {
      const response = await app.request<any>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/geoip/test?ip=${encodeURIComponent(this.testIP)}`,
      });

      this.testResult = response.data.attributes;
    } catch (error: any) {
      this.testError = error?.message || app.translator.trans('fof-geoip.admin.settings.test_error');
      this.testResult = {
        success: false,
        error: error?.message || 'Unknown error',
        httpStatus: error?.status || null,
        responseText: error?.responseText || null,
      };
    } finally {
      this.testLoading = false;
      m.redraw();
    }
  }
}
