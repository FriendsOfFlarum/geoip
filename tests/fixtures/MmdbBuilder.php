<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\fixtures;

/**
 * Builds tiny, valid MaxMind DB (.mmdb) files in memory for tests.
 *
 * Real GeoLite2/DB-IP databases are 8-130MB, far too large to commit, and
 * downloading them would put the network in CI's path. The format is
 * documented and a file holding a handful of networks is only a few hundred
 * bytes, so the tests build exactly the records they need.
 *
 * Only the subset of the spec the driver relies on is implemented: an IPv6
 * search tree (with IPv4 mapped into ::ffff:0:0/96, as real databases do), the
 * data section types the vendors actually emit, and the metadata the driver
 * reads to identify a database.
 *
 * @see https://maxmind.github.io/MaxMind-DB/
 */
class MmdbBuilder
{
    /** The spec reserves 16 zero bytes between the search tree and the data section. */
    private const DATA_SEPARATOR = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    private const METADATA_MARKER = "\xAB\xCD\xEFMaxMind.com";

    /** @var array<array{0: string, 1: int, 2: array}> [networkBinary, prefixLen, record] */
    private array $networks = [];

    public function __construct(
        private string $databaseType,
        private int $ipVersion = 6,
        private ?int $buildEpoch = null
    ) {
        $this->buildEpoch ??= time();
    }

    /**
     * Add a network. The IP may be v4 or v6; v4 is mapped into the v6 tree so
     * a single database serves both, which is how real databases are built.
     */
    public function add(string $ip, int $prefixLength, array $record): self
    {
        $packed = inet_pton($ip);

        if ($packed === false) {
            throw new \InvalidArgumentException("Invalid IP: $ip");
        }

        // Place IPv4 under a 96-bit zero prefix rather than the conventional
        // ::ffff:0:0/96. The reader finds the IPv4 subtree by walking 96 left
        // (zero) branches from the root without inspecting the bits, so this
        // is where it will look; real databases are laid out to satisfy the
        // same walk. See the spine built in build().
        if (strlen($packed) === 4) {
            $packed = str_repeat("\0", 12).$packed;
            $prefixLength += 96;
        }

        $this->networks[] = [$this->toBits($packed), $prefixLength, $record];

        return $this;
    }

    public function write(string $path): string
    {
        file_put_contents($path, $this->build());

        return $path;
    }

    private function toBits(string $packed): string
    {
        $bits = '';

        foreach (str_split($packed) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return $bits;
    }

    /**
     * Build the binary search tree, data section and metadata.
     *
     * Nodes are built as a simple binary trie: each network's bits walk the
     * tree, creating nodes as needed, and the final node points at a data
     * record. Record values below the node count are node pointers, the node
     * count itself means "no data", and anything above is a data pointer.
     */
    private function build(): string
    {
        // node = [left, right], each either ['node', idx], ['data', key] or null
        $nodes = [[null, null]];

        // The reader locates the IPv4 subtree by walking 96 *left* branches
        // from the root (see Reader::ipV4StartNode), regardless of the actual
        // ::ffff:0:0/96 bits. A trie built only from inserted prefixes has no
        // such spine, so IPv4 lookups fall off the end and return null. Build
        // the spine up front; inserting the mapped v4 networks then reuses it.
        if ($this->ipVersion === 6) {
            $current = 0;

            for ($i = 0; $i < 96; $i++) {
                $nodes[] = [null, null];
                $nodes[$current][0] = ['node', count($nodes) - 1];
                $current = count($nodes) - 1;
            }
        }

        $dataKeys = [];

        foreach ($this->networks as [$bits, $prefixLength, $record]) {
            $key = count($dataKeys);
            $dataKeys[$key] = $record;

            $current = 0;

            for ($i = 0; $i < $prefixLength; $i++) {
                $bit = (int) $bits[$i];
                $isLast = $i === $prefixLength - 1;

                if ($isLast) {
                    $nodes[$current][$bit] = ['data', $key];
                    break;
                }

                $next = $nodes[$current][$bit];

                if ($next === null || $next[0] !== 'node') {
                    $nodes[] = [null, null];
                    $next = ['node', count($nodes) - 1];
                    $nodes[$current][$bit] = $next;
                }

                $current = $next[1];
            }
        }

        $nodeCount = count($nodes);
        $recordSize = 32;

        // Serialize the data section first so pointers are known.
        $dataSection = '';
        $dataOffsets = [];

        foreach ($dataKeys as $key => $record) {
            $dataOffsets[$key] = strlen($dataSection);
            $dataSection .= $this->encode($record);
        }

        $tree = '';

        foreach ($nodes as $node) {
            foreach ([0, 1] as $side) {
                $entry = $node[$side];

                if ($entry === null) {
                    // "no data" is encoded as the node count itself.
                    $value = $nodeCount;
                } elseif ($entry[0] === 'node') {
                    $value = $entry[1];
                } else {
                    // Data pointers are offsets into the data section, biased
                    // by the node count + the 16-byte reserved gap the spec
                    // defines between the tree and the data section.
                    $value = $nodeCount + 16 + $dataOffsets[$entry[1]];
                }

                $tree .= pack('N', $value);
            }
        }

        $metadata = $this->encode([
            'binary_format_major_version' => 2,
            'binary_format_minor_version' => 0,
            'build_epoch'                 => $this->buildEpoch,
            'database_type'               => $this->databaseType,
            'description'                 => ['en' => 'Test fixture'],
            'ip_version'                  => $this->ipVersion,
            'languages'                   => ['en'],
            'node_count'                  => $nodeCount,
            'record_size'                 => $recordSize,
        ]);

        return $tree.self::DATA_SEPARATOR.$dataSection.self::METADATA_MARKER.$metadata;
    }

    /**
     * Encode a PHP value using the MaxMind DB data format.
     */
    private function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $this->encodeTyped(2, $value);
        }

        if (is_bool($value)) {
            // Booleans carry their value in the size field, with no payload.
            return $this->control(14, $value ? 1 : 0);
        }

        if (is_int($value)) {
            if ($value < 0) {
                throw new \InvalidArgumentException('Negative integers are not needed by these fixtures');
            }

            $bytes = $value === 0 ? '' : ltrim(pack('N', $value), "\0");

            // uint32 is type 6.
            return $this->control(6, strlen($bytes)).$bytes;
        }

        if (is_float($value)) {
            // double is type 3, always 8 bytes, big-endian.
            return $this->control(3, 8).strrev(pack('d', $value));
        }

        if (is_array($value)) {
            if ($value !== [] && array_keys($value) === range(0, count($value) - 1)) {
                $payload = '';

                foreach ($value as $item) {
                    $payload .= $this->encode($item);
                }

                // array is type 11 (extended).
                return $this->control(11, count($value)).$payload;
            }

            $payload = '';

            foreach ($value as $k => $v) {
                $payload .= $this->encode((string) $k).$this->encode($v);
            }

            // map is type 7.
            return $this->control(7, count($value)).$payload;
        }

        throw new \InvalidArgumentException('Unsupported value type: '.get_debug_type($value));
    }

    private function encodeTyped(int $type, string $payload): string
    {
        return $this->control($type, strlen($payload)).$payload;
    }

    /**
     * Build the control byte(s): the top three bits are the type (or 0 with an
     * extended type byte following), the bottom five encode the size.
     */
    private function control(int $type, int $size): string
    {
        $extended = '';

        if ($type >= 8) {
            $extended = chr($type - 7);
            $typeBits = 0;
        } else {
            $typeBits = $type;
        }

        if ($size < 29) {
            $sizeBits = $size;
            $sizeBytes = '';
        } elseif ($size < 29 + 256) {
            $sizeBits = 29;
            $sizeBytes = chr($size - 29);
        } elseif ($size < 285 + 65536) {
            $sizeBits = 30;
            $sizeBytes = pack('n', $size - 285);
        } else {
            $sizeBits = 31;
            $sizeBytes = substr(pack('N', $size - 65821), 1);
        }

        return chr(($typeBits << 5) | $sizeBits).$extended.$sizeBytes;
    }
}
