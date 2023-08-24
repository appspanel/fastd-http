<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * Class Uri
 *
 * @package FastD\Http
 */
class Uri implements UriInterface
{
    /**
     * Sub-delimiters used in query strings and fragments.
     *
     * @const string
     */
    const CHAR_SUB_DELIMITERS = '!\$&\'\(\)\*\+,;=';

    /**
     * Unreserved characters used in paths, query strings, and fragments.
     *
     * @const string
     */
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * @var array<string,int> Array indexed by valid scheme names to their corresponding ports.
     */
    protected array $allowedSchemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * @var string
     */
    protected string $scheme = '';

    /**
     * @var string
     */
    protected string $userInfo = '';

    /**
     * @var string
     */
    protected string $host = '';

    /**
     * @var int|null
     */
    protected ?int $port = null;

    /**
     * @var string
     */
    protected string $path = '';

    /**
     * @var string
     */
    protected string $relationPath = '/';

    /**
     * @var array
     */
    protected array $query = [];

    /**
     * @var string
     */
    protected string $fragment = '';

    /**
     * generated uri string cache
     *
     * @var string|null
     */
    protected ?string $uriString = null;

    /**
     * Uri constructor.
     *
     * @param string $uri
     * @throws InvalidArgumentException on non-string $uri argument
     */
    public function __construct(string $uri = '')
    {
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * Operations to perform on clone.
     *
     * Since cloning usually is for purposes of mutation, we reset the
     * $uriString property so it will be re-calculated.
     */
    public function __clone()
    {
        $this->uriString = null;
    }

    /**
     * @return null|string
     */
    public function __toString()
    {
        if (null !== $this->uriString) {
            return $this->uriString;
        }

        $this->uriString = $this->createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(), // Absolute URIs should use a "/" for an empty path
            $this->query,
            $this->fragment
        );

        return $this->uriString;
    }

    /**
     * {@inheritDoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthority(): string
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritDoc}
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getRelationPath(): string
    {
        return $this->relationPath;
    }

    /**
     * {@inheritDoc}
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * {@inheritDoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritDoc}
     */
    public function withScheme(string $scheme): static
    {
        $scheme = $this->filterScheme($scheme);

        if ($scheme === $this->scheme) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->scheme = $scheme;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withUserInfo(string $user, ?string $password = null): static
    {
        $info = $user;
        if ($password) {
            $info .= ':' . $password;
        }

        if ($info === $this->userInfo) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->userInfo = $info;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withHost(string $host): static
    {
        if ($host === $this->host) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPort(?int $port): static
    {
        if(null === $port) {
            $this->port = null;

            return $this;
        }

        if ($port === $this->port) {
            // Do nothing if no change was made.
            return $this;
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%d" specified; must be a valid TCP/UDP port',
                $port
            ));
        }

        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath(string $path): static
    {
        if (str_contains($path, '?')) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }

        if (str_contains($path, '#')) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }

        $path = $this->filterPath($path);

        if ($path === $this->path) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withQuery(string $query): static
    {
        if (str_contains($query, '#')) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }

        $query = $this->filterQuery($query);

        if ($query === $this->query) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->query = $query;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withFragment(string $fragment): static
    {
        $fragment = $this->filterFragment($fragment);

        if ($fragment === $this->fragment) {
            // Do nothing if no change was made.
            return $this;
        }

        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Parse a URI into its parts, and set the properties
     *
     * @param string $uri
     */
    protected function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        if (false === $parts) {
            throw new InvalidArgumentException(
                'The source URI string appears to be malformed'
            );
        }

        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = $parts['user'] ?? '';
        $this->host = $parts['host'] ?? '';

        if (isset($parts['port'])) {
            $this->port = $parts['port'];
        } elseif('https' === $this->scheme) {
            $this->port = 443;
        } else {
            $this->port = 80;
        }

        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '/';
        $this->query = isset($parts['query']) ? $this->filterQuery($parts['query']) : [];
        $this->fragment = isset($parts['fragment']) ? $this->filterFragment($parts['fragment']) : '';

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }

        if (false !== $index = strpos($uri, '.php')) {
            $this->relationPath = substr($uri, ($index + 4));

            if (empty($this->relationPath)) {
                $this->relationPath = '/';
            }
        }
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param array $query
     * @param string $fragment
     * @return string
     */
    public function createUriString(string $scheme, string $authority, string $path, array $query, string $fragment): string
    {
        $uri = '';

        if (!empty($scheme)) {
            $uri .= sprintf('%s://', $scheme);
        }

        if (!empty($authority)) {
            $uri .= $authority;
        }

        if ($path) {
            if (empty($path) || !str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            $uri .= $path;
        }

        if ($query) {
            $uri .= sprintf('?%s', http_build_query($query));
        }

        if ($fragment) {
            $uri .= sprintf('#%s', $fragment);
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @return bool
     */
    protected function isNonStandardPort(): bool
    {
        if (in_array((int) $this->port, $this->allowedSchemes)) {
            return false;
        }

        return true;
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * @param string $scheme Scheme name.
     *
     * @return string Filtered scheme.
     */
    protected function filterScheme(string $scheme): string
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {
            return '';
        }

        if (!array_key_exists($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be any empty string or in the set (%s)',
                $scheme,
                implode(', ', array_keys($this->allowedSchemes))
            ));
        }

        return $scheme;
    }

    /**
     * Filters the path of a URI to ensure it is properly encoded.
     *
     * @param string $path
     * @return string
     */
    protected function filterPath(string $path): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $path
        );
    }

    /**
     * Filter a query string to ensure it is properly encoded.
     *
     * Ensures that the values in the query string are properly urlencoded.
     *
     * see: http://php.net/manual/en/function.parse-str.php#119484
     *
     * @param string $query
     * @return array
     */
    protected function filterQuery(string $query): array
    {
        $queryInfo = [];

        foreach (explode('&', $query) as $part) {
            [$name, $value] = explode('=', (!str_contains($part, '=') ? "{$part}=" : $part), 2);

            $name = rawurldecode($name);
            $value = rawurldecode($value);

            if (0 === preg_match_all('/\[([^\]]*)\]/m', $name, $matches)) {
                $queryInfo[$name] = $value;
                continue;
            }

            $name   = substr($name, 0, strpos($name, '['));
            $keys   = array_merge([$name], $matches[1]);
            $target = &$queryInfo;

            foreach ($keys as $index) {
                if ('' === $index) {
                    $target = &$target[];
                } else {
                    $target = &$target[$index];
                }
            }

            $target = $value;
        }

        return $queryInfo;
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    protected function splitQueryValue(string $value): array
    {
        $data = explode('=', $value, 2);

        if (1 === count($data)) {
            $data[] = null;
        }

        return $data;
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * @param null|string $fragment
     * @return string
     */
    protected function filterFragment(?string $fragment): string
    {
        if (null === $fragment) {
            $fragment = '';
        }

        if (!empty($fragment) && str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        return $this->filterQueryOrFragment($fragment);
    }

    /**
     * Filter a query string key or value, or a fragment.
     *
     * @param string $value
     * @return string
     */
    protected function filterQueryOrFragment(string $value): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMITERS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    /**
     * URL encode a character returned by a regex.
     *
     * @param array $matches
     * @return string
     */
    protected function urlEncodeChar(array $matches): string
    {
        return rawurlencode($matches[0]);
    }
}
