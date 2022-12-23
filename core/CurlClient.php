<?php
namespace HfCore;

/**
 * CurlClient
 */
class CurlClient {
    /**
     * Resultat
     * @var CurlResult
     */
    protected $result = null;

    /**
     * Ursprüngliche URL für Aufruf
     * @var string
     */
    protected $url = '';

    /**
     * URL Parameter (GET)
     * @var string[]
     */
    protected $urlParameters = [];

    /**
     * Cookies verwenden?
     * @var boolean
     */
    protected $cookiesEnabled = false;

    /**
     * Cookies
     * @var array
     */
    protected $cookiesData = [];

    /**
     * Kein Expect Header senden
     * @var bool
     */
    protected $avoidExpect = false;

    /**
     * HTTP Header
     * @var array
     */
    protected $httpHeader = [];

    /**
     * Zeichencodierung
     * @var string|null
     */
    protected $encoding = null;

    /**
     * Zeichencodierung intern
     * @var string
     */
    protected $encodingInternal = 'UTF-8';

    /**
     * Database Cache
     * @var CacheDatabase
     */
    protected $cache = null;

    /**
     * Handle
     * @var int
     */
    protected $handle;

    /**
     * CurlClient constructor.
     * @param string|null $url
     */
    public function __construct(?string $url = null) {
        $this->handle = curl_init();
        $this->setTimeout(10);

        if ($url)
            $this->setURL($url);
    }

    /**
     * Cache für Request verwenden
     * @param DateTime|DateInterval|string|null $maxAge
     * @param string $type z.B. rssfeed
     * @param string $identifier z.B. http://www.google.ch/
     * @return $this
     */
    public function setCache($maxAge = null, ?string $type = null, ?string $identifier = null) {
        if (!$this->url && !$identifier)
            throw new \Exception('Kein Identifier für Cache oder URL gesetzt');

        $this->cache = SystemCache::db($type ? $type : 'curl', $identifier ? $identifier : $this->url, $maxAge);
        return $this;
    }

    /**
     * Einstellung setzen
     * @param int $name
     * @param mixed $value
     * @return $this
     */
    public function setOption(int $name, $value) {
        curl_setopt($this->handle, $name, $value);
        return $this;
    }

    /**
     * Mehrere Einstellungen setzen
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) {
        curl_setopt_array($this->handle, $options);
        return $this;
    }

    /**
     * URL setzen
     * @param string $url
     * @return $this
     */
    public function setURL(string $url) {
        $this->url = $url;
        $this->setOption(CURLOPT_URL, $url);
        return $this;
    }

    /**
     * Konfigurierte URL
     * @return string
     */
    public function getURL(): string {
        return $this->url;
    }

    /**
     * Effektive URL (inkl. Umleitungen)
     * @return string
     */
    public function getEffectiveURL(): string {
        return $this->getInfo(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * Port setzen
     * @param int $port
     * @return $this
     */
    public function setPort(int $port) {
        $this->setOption(CURLOPT_PORT, $port);
        return $this;
    }

    /**
     * Cookies aktivieren
     * @param boolean $cookiesEnabled
     * @return $this
     */
    public function cookiesEnabled(bool $cookiesEnabled) {
        $this->cookiesEnabled = $cookiesEnabled;
        return $this;
    }

    /**
     * Bestehende Cookies löschen, Cookies setzen und Verwendung aktivieren
     * @param string $cookies
     * @return $this
     */
    public function setCookies(string $cookies) {
        $this->cookiesEnabled(true);
        $this->cookiesData = [];
        $data = null;
        preg_match_all('/(.+)\=(.+)\;/Ui', $cookies, $data);
        foreach ($data[1] as $i => $name) {
            $name = trim($name);
            $this->cookiesData[$name] = $data[2][$i];
        }
        return $this;
    }

    /**
     * Ein oder Mehrere Cookies setzen
     * @param string|[] $name
     * @param string $value
     * @return $this
     */
    public function setCookie($name, string $value = null) {
        $this->cookiesEnabled(true);
        if (is_array($name))
            $this->cookiesData = array_merge($this->cookiesData, $name);
        else
            $this->cookiesData[$name] = $value;

        return $this;
    }

    /**
     * Gesetzte Cookies abrufen
     * @return string
     * @throws \Exception
     */
    public function getCookies(): string {
        if (!$this->cookiesEnabled)
            throw new \Exception('Cookies sind nicht aktiviert');

        $cookies = '';
        foreach ($this->cookiesData as $name => $value)
            $cookies .= $name.'='.$value.'; ';

        return trim($cookies);
    }

    /**
     * Einzelnes Cookie abrufen
     * @param $name
     * @return null|string
     */
    public function getCookie($name): ?string {
        return isset($this->cookiesData[$name]) ? $this->cookiesData[$name] : null;
    }


    /**
     * Kein Expect-Header senden
     * @param boolean $avoidExpect
     * @return $this
     */
    public function avoidExpect(bool $avoidExpect) {
        $this->avoidExpect = $avoidExpect;
        return $this;
    }

    /**
     * Downloadgrösse Limitieren
     * @param Bytes|int $limit
     * @return $this
     */
    public function setDownloadLimit($limit) {
        if ($limit instanceof Bytes)
            $limit = $limit->value;

        $this->setOption(CURLOPT_BUFFERSIZE, 128);
        $this->setOption(CURLOPT_NOPROGRESS, false);
        $this->setOption(CURLOPT_PROGRESSFUNCTION, function ($resource, $downloadSize = 0, $downloaded = 0) use ($limit) {
            if ($downloadSize > $limit || $downloaded > $limit)
                return 1;

            return 0;
        });
        return $this;
    }

    /**
     * Zeichencodierung festlegen
     * @param string $encoding
     * @return $this
     */
    public function setEncoding(string $encoding) {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * SSL Verbindung verifizieren?
     * @param boolean $verify
     * @return $this
     */
    public function verifySSL(bool $verify = true) {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $verify);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, $verify ? 2 : 0);
        return $this;
    }

    /**
     * Timeout in Sekunden
     * @param int $timeout Sekunden
     * @return $this
     */
    public function setTimeout(int $timeout) {
        $this->setOption(CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * Basic Authentifzierung
     * @param string $user
     * @param string $password
     * @return $this
     */
    public function setBasicAuth(string $user, string $password) {
        $this->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOption(CURLOPT_USERPWD, $user.':'.$password);
        return $this;
    }

    /**
     * Setze einen oder mehrere HTTP Header
     * @param string|array $header
     * @param bool $append
     * @return $this
     */
    public function setHTTPHeader($header, bool $append = true) {
        if (!$append)
            $this->httpHeader = [];

        if (!is_array($header))
            $header = [$header];

        foreach ($header as $data) {
            list($name, $value) = explode(':', $data);
            $this->httpHeader[strtolower($name)] = $data;
        }
        return $this;
    }

    /**
     * Setze User-Agent
     * @param string $useragent
     * @return $this
     */
    public function setUserAgent(string $useragent) {
        $this->setOption(CURLOPT_USERAGENT, $useragent);
        return $this;
    }

    /**
     * Request als POST ausführen
     * @return $this
     */
    public function setMethodPOST() {
        $this->setOption(CURLOPT_POST, true);
        return $this;
    }

    /**
     * Request als GET ausführen
     * @return $this
     */
    public function setMethodGET() {
        $this->setOption(CURLOPT_HTTPGET, true);
        return $this;
    }

    /**
     * Setze POST Daten und führe Request als POST aus
     * @param string|array $data Querystring oder Array
     * @return $this
     */
    public function setPOSTData($data) {
        $this->setMethodPOST();
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        $this->log(sprintf('Setze Curl POST-Daten (%s)', gettype($data)), $data);
        return $this;
    }

    /**
     * URL (GET) Parameter setzen
     * @param array $data
     * @return $this
     */
    public function setURLParameters(array $data) {
        $this->urlParameters = $data;
        $this->log(sprintf('Setze Curl URL-Parameter (%s)', gettype($data)), $data);
        return $this;
    }

    /**
     * Einzelnen URL (GET) Parameter hinzufügen
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addURLParameter(string $name, $value) {
        $this->urlParameters[$name] = $value;
        return $this;
    }

    /**
     * URL (GET) Parameter hinzufügen
     * @param array $data
     * @return $this
     */
    public function addURLParameters(array $data) {
        $this->urlParameters = array_merge($this->urlParameters, $data);
        return $this;
    }

    /**
     * Umleitungen durch "Location: "-Header folgen
     * @param boolean $state
     * @return $this
     */
    public function followLocation(bool $state) {
        $this->setOption(CURLOPT_FOLLOWLOCATION, $state);
        return $this;
    }

    /**
     * Ausführen
     * @param boolean $noCache Cache ignorieren und neu laden
     * @return CurlResult
     * @throws \Exception
     */
    public function exec(bool $noCache = false) {
        $cdata = null;
        if (!$this->cache || $noCache || !$cdata = $this->cache->get()) {
            $this->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->setOption(CURLOPT_HEADER, true);

            if (count($this->urlParameters))
                $this->setOption(CURLOPT_URL, $this->getURL().'?'.http_build_query($this->urlParameters));

            if ($this->cookiesEnabled && count($this->cookiesData))
                $this->setHTTPHeader('Cookie: '.$this->getCookies());

            if ($this->avoidExpect)
                $this->setHTTPHeader('Expect: ');

            if (count($this->httpHeader))
                $this->setOption(CURLOPT_HTTPHEADER, $this->httpHeader);

            // Ausführen
            $raw = curl_exec($this->handle);

            if (!$this->encoding) {
                $encoding = self::getEncoding($raw);
                //$this->log(sprintf('Curl Charset automatisch auf "%s" gesetzt', $encoding));
                $this->setEncoding($encoding);
            }

            $errorCode = $this->getErrorCode();
            if ($errorCode)
                throw new \Exception(sprintf('Fehler bei Curl-Request: "%s" URL: "%s"', $this->getError(), $this->getURL()), $errorCode);

            // Resultat parsen
            $this->result = new CurlResult($this, $this->decode($raw), $this->getInfo(CURLINFO_HEADER_SIZE), $this->getInfo(CURLINFO_HTTP_CODE));
            $this->log(sprintf('Curl Request "%s" (%ss)', $this->getEffectiveURL(), $this->getDuration()), $this->result->getDebugInfo($this->getMimeType()), 1);

            if ($this->cookiesEnabled) {
                $data = null;
                preg_match_all('/Set-Cookie: (.+)\=(.+)\;/Ui', $this->result->getHeader(), $data);
                foreach ($data[1] as $i => $name) {
                    $name = trim($name);
                    $this->cookiesData[$name] = $data[2][$i];
                    $this->log(sprintf('Curl setzte Cookie "%s=%s"', $name, $this->cookiesData[$name]));
                }
            }

            // Resultat in Cache legen
            if ($this->cache) {
                $this->log('Curl setze Resultat in Cache');
                $this->cache->set($this->result->toCache());
            }
        }
        else if ($cdata) {
            $this->result = new CurlResult($this, $cdata[0], (int)$cdata[1], (int)$cdata[2]);
            $this->log(sprintf('Curl Request "%s" aus Cache', $this->getEffectiveURL()), $this->result);
        }

        return $this->result;
    }

    /**
     * Geparstes Resultat
     * @return CurlResult
     * @throws \Exception
     */
    public function getResult(): CurlResult {
        if (!$this->result)
            throw new \Exception('Request wurde noch nicht ausgeführt');

        return $this->result;
    }

    /**
     * Curl Error-Code
     * @return int
     */
    public function getErrorCode(): int {
        return curl_errno($this->handle);
    }

    /**
     * Curl Error-Meldung
     * @return string
     */
    public function getError(): string {
        return curl_error($this->handle);
    }

    /**
     * Antwortzeit in Sekunden
     * @return double
     */
    public function getDuration(): float {
        return $this->getInfo(CURLINFO_TOTAL_TIME);
    }

    /**
     * Informationen über den vergangenen Request
     * @param int $opt
     * @return mixed|array
     */
    public function getInfo(int $opt = 0) {
        return curl_getinfo($this->handle, $opt);
    }

    /**
     * Mime-Type
     * @return string
     */
    public function getMimeType(): string {
        return explode(';', $this->getInfo(CURLINFO_CONTENT_TYPE))[0];
    }

    /**
     * Content-Länge
     * @return int
     */
    public function getLength(): int {
        return $this->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    }

    /**
     * @return int|resource
     */
    public function getHandle() {
        return $this->handle;
    }

    /**
     * Für Extern konvertieren
     * @param string $str
     * @return string
     */
    public function encode(string $str): string {
        if (!strcasecmp($this->encodingInternal, $this->encoding))
            return $str;

        //$this->log(sprintf('Curl Codiere "%s" zu "%s"', $this->encodingInternal, $this->encoding));
        return mb_convert_encoding($str, $this->encoding, $this->encodingInternal);
    }

    /**
     * Für Intern konvertieren
     * @param string $str
     * @return string
     */
    public function decode(string $str): string {
        if (!strcasecmp($this->encodingInternal, $this->encoding))
            return $str;

        //$this->log(sprintf('Curl Dekodiere "%s" zu "%s"', $this->encoding, $this->encodingInternal));
        return mb_convert_encoding($str, $this->encodingInternal, $this->encoding);
    }

    public function __destruct() {
        curl_close($this->handle);
    }

    /**
     * Encoding auslesen
     * @param string $raw
     * @return string
     */
    public static function getEncoding(string $raw): string {
        $data = null;
        if (preg_match('/Content-Type\: .+\; charset\=([^\"]+)'."\n".'/Ui', $raw, $data))
            return trim(strtoupper($data[1]));

        if (preg_match('/charset\=([^\"]+)["\'\\'.PHP_EOL.']/Ui', $raw, $data))
            return trim(strtoupper($data[1]));

        if (preg_match('/encoding\=\"([^\"]+)\"/Ui', $raw, $data))
            return trim(strtoupper($data[1]));

        return 'UTF-8';
    }

    /**
     * Neuer Curl-Client
     * @param string $url
     * @return self
     */
    public static function create(?string $url = null) {
        return new CurlClient($url);
    }

    /**
     * Header parsen
     * @param string $raw
     * @return array
     */
    public static function parseHTTPHeaders(string $raw): array {
        $headers = [];
        $key = '';
        foreach (explode("\n", $raw) as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                else if (is_array($headers[$h[0]]))
                    $headers[$h[0]] = array_merge($headers[$h[0]], [trim($h[1])]);
                else
                    $headers[$h[0]] = array_merge([$headers[$h[0]]], [trim($h[1])]);

                $key = $h[0];
            }
            else {
                if (substr($h[0], 0, 1) == "\t")
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                else if (!$key)
                    $headers[0] = trim($h[0]);
            }
        }

        return $headers;
    }

    public function log($str) {

    }

}

class CurlResult {
    /**
     * Resultat
     * @var string
     */
    protected $rawResult = '';

    /**
     * Header
     * @var string
     */
    public $header = '';

    /**
     * Headerlänge
     * @var int
     */
    public $headerSize = 0;

    /**
     * Body
     * @var string
     */
    public $body = '';

    /**
     * Http Statuscode
     * @var int
     */
    public $httpCode = 0;

    /**
     * @var CurlClient
     */
    public $client;

    public function __construct(CurlClient $client, string $rawResult, int $headerSize, int $httpCode) {
        $this->client = $client;
        $this->rawResult = $rawResult;
        $this->headerSize = $headerSize;
        $this->header = substr($this->rawResult, 0, $this->headerSize);
        $this->body = substr($this->rawResult, $this->headerSize);
        $this->httpCode = $httpCode;
    }

    /**
     * Body
     * @return string
     */
    public function getBody(): string {
        return $this->body;
    }

	/**
	 * JSON dekodieren
	 * @param bool $associative Rückgabe als array
	 * @return object|array
	 */
	public function getFromJSON(bool $associative = false) {
		return json_decode($this->body, $associative);
	}

    /**
     * XML dekodieren
     * @param boolean $namespaces
     * @return Xml
     */
    public function getFromXML(bool $namespaces = false): Xml {
        $body = $this->body;
        if (preg_match('/\<\?xml.*encoding=".*".*\?\>/i', $body))
            $body = preg_replace('/encoding=".*"/Ui', '', $body, 1);

        return Xml::parseString($body, $namespaces);
    }

    /**
     * HTML Parser
     * @return HTMLParser
     */
    public function getHTMLParser(): HTMLParser {
        $parser = new HTMLParser();
        $parser->loadHTML($this->body);
        return $parser;
    }

    /**
     * Header
     * @return string
     */
    public function getHeader(): string {
        return $this->header;
    }

    /**
     * Komplette Antwort ungeparst
     * @return string
     */
    public function getRawResult(): string {
        return $this->rawResult;
    }

    /**
     * Header parsen
     * @return array
     */
    public function getHeaderParsed(): array {
        return CurlClient::parseHTTPHeaders($this->getHeader());
    }

    /**
     * Content Type
     * @return null|string
     */
    public function getContentType(): ?string {
        $header = array_change_key_case($this->getHeaderParsed(), CASE_LOWER);
        return isset($header['content-type']) ? (is_array($header['content-type']) ? (string)array_pop($header['content-type']) : (string)$header['content-type']) : null;
    }

    /**
     * Resultat für Cache codieren
     * @return array
     */
    public function toCache(): array {
        return [$this->rawResult, $this->headerSize, $this->httpCode];
    }

    /**
     * @param null|string $mimeType
     * @return CurlResult
     */
    public function getDebugInfo(?string $mimeType = null) {
        $debug = clone $this;
        if (!preg_match('/text/', $mimeType) && !preg_match('/json/', $mimeType) && !preg_match('/application\/xml/', $mimeType))
            $debug->body = '[['.$mimeType.']]';

        unset($debug->client);
        return $debug;
    }

    public function log($str) {

    }

}
