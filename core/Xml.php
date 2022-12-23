<?php
namespace HfCore;

/**
 * XML Node Klasse
 *
 * Erlaubt das erstellen und editieren hochkomplexer XML Strukturen
 */
class Xml implements \Countable, \ArrayAccess, \Iterator {
	/**
	 * @var Xml[]|string[]
	 */
	public $contents = [];

	/**
	 * @var Xml|null
	 */
	public $parent = null;

	/**
	 * @var string|null
	 */
	public $name = 'node';

	/**
	 * @var string[]
	 */
	public $attributes = [];

	/**
	 * @var int
	 */
	public $position = 0;

	/**
	 * @var string[]
	 */
	public $data = [];

	/**
	 * @var bool
	 */
	public $collapseEmpty = false;

	/**
	 * Neuer Node
	 * @param string|null $name Name des Nodes
	 */
	public function __construct(?string $name = null) {
		$this->name = $name;
	}

	/**
	 * Element Name überschreiben
	 * @param string $name
	 * @return $this
	 */
	public function name(string $name): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Automatisch "kollabieren" wenn leer
	 * @param boolean $collapse
	 * @return $this
	 */
	public function collapse(bool $collapse): self {
		$this->collapseEmpty = $collapse;
		return $this;
	}

	/**
	 * Attribut setzen
	 * ist $value = null, so wird dass Attribut entfernt
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function attr(string $name, ?string $value = null): self {
		$this->attributes[$name] = $this->escape($value);
		if ($value === null)
			unset($this->attributes[$name]);

		return $this;
	}

	/**
	 * Attribut abfragen
	 * @param string $name
	 * @return string
	 */
	public function getAttr(string $name): ?string {
		if (isset($this->attributes[$name]))
			return $this->unescape($this->attributes[$name]);

		return null;
	}

	/**
	 * Element anfügen
	 * @param Xml|string $content
	 * @return $this
	 */
	public function append($content): self {
		if ($content instanceof Xml)
			$content->detach()->parent = $this;

		$this->contents[] = $content;
		return $this;
	}

	/**
	 * Content ersetzen
	 * @param string|Xml $content
	 * @return Xml
	 */
	public function setContent($content): self {
		$this->clear()->append($content);
		return $this;
	}

	/**
	 * Element Komplett löschen
	 * @return $this
	 */
	public function remove(): self {
		$this->detach();
		return $this;
	}

	/**
	 * Fügt Content nach Element ein
	 * @param string|Xml $content
	 * @return $this
	 */
	public function after($content): self {
		if (!$this->parent())
			throw new SystemException('Nebenanfügen nicht möglich, da kein Parent-Element vorhanden');
		$this->parent()->append($content);
		return $this;
	}

	/**
	 * Fügt Content vor Element ein
	 * @param string|Xml $content
	 * @return $this
	 */
	public function before($content): self {
		if (!$this->parent())
			throw new SystemException('Nebenanfügen nicht möglich, da kein Parent-Element vorhanden');
		$this->parent()->prepend($content);
		return $this;
	}

	/**
	 * An anderes Element anfügen
	 * @param Xml $element
	 * @return $this
	 */
	public function appendTo(Xml $element): self {
		$element->append($this);
		return $this;
	}

	/**
	 * An anderes Element vorne anfügen
	 * @param Xml $element
	 * @return $this
	 */
	public function prependTo(Xml $element): self {
		$element->prepend($this);
		return $this;
	}

	/**
	 * Einschliessende Tags entfernen
	 * @return $this
	 */
	public function unwrap(): self {
		$this->name = null;
		return $this;
	}

	/**
	 * Element aus Content entfernen
	 * @return $this
	 */
	public function detach(): self {
		if (!$this->parent())
			return $this;

		$parent = $this->parent();
		foreach ($parent->contents as $i => $content) {
			if ($content === $this)
				unset($parent->contents[$i]);
		}
		return $this;
	}

	/**
	 * Entfernt alle Inhalte
	 * @return $this
	 */
	public function clear(): self {
		$this->contents = [];
		return $this;
	}

	/**
	 * Text anfügen
	 * @param string $text
	 * @param array|string ...$sprintfargs
	 * @return $this
	 */
	public function appendText(?string $text, ...$sprintfargs): self {
		if (count($sprintfargs))
			$text = call_user_func_array('sprintf', $sprintfargs);

		array_push($this->contents, $this->escape($text));
		return $this;
	}

	/**
	 * Element vorne anfügen
	 * @param string|Xml $content (String, XML)
	 * @return $this
	 */
	public function prepend($content): self {
		if ($content instanceof Xml)
			$content->detach()->parent = $this;

		array_unshift($this->contents, $content);
		return $this;
	}

	/**
	 * Inner Text setzen oder auslesen
	 * @param string $text
	 * @param array|string ...$sprintfargs
	 * @return $this|string
	 */
	public function text(?string $text = null, ...$sprintfargs) {
		if ($text === null) {
			$text = '';
			foreach ($this->contents as $content) {
				if (is_string($content))
					$text .= $content;
			}
			return $this->unescape($text);
		}
		if (count($sprintfargs))
			$text = call_user_func_array('sprintf', $sprintfargs);

		$this->contents = [$this->escape($text)];
		return $this;
	}

	/**
	 * Inner Text setzen
	 * @param string $text
	 * @param array|string ...$sprintfargs
	 * @return $this
	 */
	public function setText(?string $text = '', ...$sprintfargs) {
		$args = func_get_args();
		if ($args[0] === null)
			$args[0] = '';
		return call_user_func_array([$this, 'text'], $args);
	}

	/**
	 * Gibt den Text zurück
	 * @param string $child
	 * @return string
	 */
	public function getText(?string $child = null) {
		if ($child !== null)
			return $this->get($child)->getText();

		return $this->text();
	}

	/**
	 * Erstes Element zurückgeben
	 * @return string|Xml|null
	 */
	public function first() {
		return isset($this->contents[0]) ? $this->contents[0] : null;
	}

	/**
	 * Zeichnen
	 * @return string
	 */
	public function draw(): string {
		$output = '';
		if ($this->name) {
			$output .= '<'.$this->name;

			if (count($this->attributes)) {
				foreach ($this->attributes as $name => $value)
					$output .= ' '.$name.'="'.$value.'"';
			}
		}

		if (count($this->contents) || !$this->collapseEmpty) {
			$output .= '>';
			foreach ($this->contents as $content) {
				if ($content instanceof self)
					$output .= $content->draw();
				else
					$output .= (string)$content;
			}

			if ($this->name)
				$output .= '</'.$this->name.'>';
		}
		else if ($this->name)
			$output .= ' />';

		return $output;
	}

	/**
	 * String XML Escape
	 * @param string $str
	 * @return string
	 */
	protected function escape(?string $str): ?string {
		if ($str instanceof LanguageString)
			$str = $str->toString();

		return Template::escape($str);
	}

	/**
	 * SpecialChars entfernen
	 * @param string $str
	 * @return string
	 */
	protected function unescape(?string $str): ?string {
		return Template::unescape($str);
	}

	/**
	 * Finde nodes aufgrund eines selectors
	 * @param string $selector z.B. '.classname tr' , 'table#test tr', '#idname'
	 * @return self[]
	 */
	public function find(string $selector): array {
		$match = false;
		return $this->findChildren($selector, $match, true);
	}

	/**
	 * Durchsucht Childnodes nach Selektor
	 * @param string $selector
	 * @param boolean $match
	 * @param boolean $first
	 * @return self[]
	 */
	protected function findChildren(string $selector, bool &$match, bool $first = false): array {
		$found = [];
		$selectors = explode(' ', $selector);

		if (!$first) {
			$selector = array_shift($selectors);

			if ($this->is($selector)) {
				if (!count($selectors))
					array_push($found, $this);
				$match = true;
			}
			else
				array_unshift($selectors, $selector);
		}

		// Unterelemente durchsuchen
		foreach ($this->contents as $content) {
			if ($content instanceof Xml)
				$found = array_merge($found, $content->findChildren(implode(' ', $selectors), $match));
		}

		return $found;
	}

	/**
	 * Gibt alle passenden Childnodes zurück
	 * @param string $selector
	 * @return self[]
	 */
	public function children(?string $selector): array {
		$found = [];
		foreach ($this->contents as $content) {
			if ($content instanceof Xml && $content->is($selector))
				array_push($found, $content);
		}
		return $found;
	}

	/**
	 * Gibt erstes passendes Childnode zurück
	 * @param string $selector
	 * @return self|null
	 */
	public function child(?string $selector): ?self {
		foreach ($this->contents as $content) {
			if ($content instanceof Xml && $content->is($selector))
				return $content;
		}
		return null;
	}

	/**
	 * Prüft ob Node auf Selektor passt
	 * @param string $selector
	 * @return $this|null
	 */
	public function is(?string $selector): ?self {
		$match = 0;
		$mismatch = 0;

		$data = [];
		preg_match('/([\w-]+)?(\#([\w-]+))?(\.([\w-]+))?(\:([\w-]+))?/i', $selector, $data);
		$name = isset($data[1]) ? $data[1] : null;
		$id = isset($data[3]) ? $data[3] : null;
		$class = isset($data[5]) ? $data[5] : null;
		$pseudo = isset($data[7]) ? $data[7] : null;

		// Name
		if ($name && $this->name == $name)
			$match++;
		else if ($name)
			$mismatch++;

		// ID
		if ($id && isset($this->attributes['id']) && $this->attributes['id'] == $id)
			$match++;
		else if ($id)
			$mismatch++;

		// Klasse
		if ($class && isset($this->classes) && $this->hasClass($class))
			$match++;
		else if ($class)
			$mismatch++;

		// Treffer?
		if ($match && !$mismatch)
			return $this;

		return null;
	}

	/**
	 * Countable-Interface
	 * Gibt Anzahl Child-Nodes zurück
	 * @return int
	 */
	public function count(): int {
		return count($this->contents);
	}

	/**
	 * ArrayAccess-Interface
	 * Node setzen. Alle vorhanden Nodes werden überschrieben. Ist keiner vorhanden, wird ein neuer erstellt.
	 * z.B. $node['test] = 'gam';
	 * @param string $offset
	 * @param string|Xml $value
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			throw new NotImplementedException('Pending function'); // Macht das sinn?
		}
		else {
			$childs = $this->children($offset);
			// Element vereits vorhanden?
			if (count($childs)) {
				foreach ($childs as $child)
					if (is_string($value))
						$child->clear()->text($value);
					else
						$child->clear()->append($value);
			}
			else {
				$newchild = new self($offset);
				// String escapen
				if (is_string($value))
					$newchild->text($value);
				else
					$newchild->append($value);
				$this->append($newchild);
			}
		}
	}

	/**
	 * ArrayAccess-Interface
	 * @param string $name
	 * @return boolean
	 */
	public function offsetExists($name): bool {
		return $this->child($name) ? true : false;
	}

	/**
	 * ArrayAccess-Interface
	 * @param string $name
	 */
	public function offsetUnset($name) {
		foreach ($this->children($name) as $child)
			$child->detach();
	}

	/**
	 * ArrayAccess-Interface
	 * Gibt den Inhalt des Nodes $name zurück oder null
	 * @param mixed $name
	 * @return string|Xml|null
	 */
	public function offsetGet($name) {
		return is_int($name) ? $this->contents[$name] : $this->child($name);
	}

	/**
	 * Node $name hinzufügen mit $content Inhalt
	 * @param string $name
	 * @param string|Xml $content \Xml|string
	 * @return $this
	 */
	public function set(string $name, $content = ''): self {
		$this->append(Xml::create($name)->append($content));
		return $this;
	}

	/**
	 * Neuer Node hinzufügen
	 * @param string $name
	 * @return self
	 */
	public function add(string $name): self {
		return Xml::create($name)->appendTo($this);
	}

	/**
	 * Node zurückgeben aufgrund des Namens
	 * @param string $name
	 * @return self|null
	 */
	public function get(string $name): ?self {
		foreach ($this->contents as $content) {
			if ($content instanceof self && $content->name == $name)
				return $content;
		}

		return null;
	}

	/**
	 * Existiert node?
	 * @param string $name
	 * @return boolean
	 */
	public function exists(string $name): bool {
		return ($this->get($name) !== null);
	}

	/**
	 * Iterator-Interface
	 */
	function rewind() {
		$this->position = 0;
	}

	/**
	 * Iterator-Interface
	 * @return Xml|string
	 */
	function current() {
		return $this->contents[$this->position];
	}

	/**
	 * Iterator-Interface
	 * @return int
	 */
	function key() {
		return $this->position;
	}

	/**
	 * Iterator-Interface
	 */
	function next() {
		$this->position++;
	}

	/**
	 * Iterator-Interface
	 * @return bool
	 */
	function valid() {
		return isset($this->contents[$this->position]);
	}

	/**
	 * Gibt einen neuen XML node erzeugen
	 * @param string $name
	 * @param string|Xml $arguments wird als string oder node angefügt
	 * @return self
	 */
	public static function __callStatic(string $name, $arguments) {
		$node = self::create($name);

		foreach ($arguments as $argument)
			if ($argument instanceof self)
				$node->append($argument);
			else if ($argument)
				$node->appendText($argument);

		return $node;
	}

	/**
	 * Neuer Node
	 * @param string $name
	 * @return Xml
	 */
	public static function create(string $name) {
		return new self($name);
	}

	/**
	 * XML String in Xml umwandeln
	 * @param string $str
	 * @param bool $namespaces
	 * @return Xml
	 */
	public static function parseString(string $str, bool $namespaces = false) {
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($str);

		// Parsefehler suchen
		if ($xml === false) {
			$errors = [];
			foreach (libxml_get_errors() as $error)
				$errors[] = $error->message;

			libxml_clear_errors();
			$log = new Log('XML-Error-Debug');
			$log->setInfo($str);
			throw new SystemException(implode(', ', $errors), 500);
		}

		return Xml::parseNode($xml, $namespaces);
	}

	/**
	 * SimpleXml String in Xml umwandeln
	 * @param SimpleXMLElement $node
	 * @param bool $namespaces
	 * @return Xml
	 */
	private static function parseNode(SimpleXMLElement $node, bool $namespaces) {
		$new = Xml::create($node->getName());
		if ($namespaces) {
			$count = 0;
			foreach ($node->getDocNamespaces() as $ns => $namespace) {
				foreach ($node->children($ns, true) as $subnode) {
					$new->append(Xml::parseNode($subnode, $namespaces));
					$count++;
				}
			}

			if (!$count)
				$new->text($node);
		}
		else {
			if ($node->count() != 0) {
				foreach ($node->children() as $subnode)
					$new->append(Xml::parseNode($subnode, $namespaces));
			}
			else
				$new->text($node);
		}

		foreach ($node->attributes() as $attr)
			$new->attr($attr->getName(), $attr);

		return $new;
	}

	/**
	 * Node in Debug-string umwandeln
	 * @param string $break
	 * @param int $index nicht setzen!
	 * @return string
	 */
	public function prints(string $break = '<br/>', int $index = 0) {
		$data = $break.str_repeat('- - ', $index).$this->name;
		$index++;

		foreach ($this->attributes as $name => $value)
			$data .= $break.str_repeat('  ', $index).$name.'="'.$value.'"';

		foreach ($this->contents as $item)
			if ($item instanceof self)
				$data .= $item->prints($break, $index);

		// Inhalt ebenfalls ausgeben
		if (count($this->contents) == 1 && !($this->contents[0] instanceof self))
			$data .= ' => '.$this->contents[0];

		return $data;
	}

	/**
	 * Übergeordneter Node
	 * @return Xml|null
	 */
	public function parent(): ?self {
		return $this->parent;
	}

	public function serialize() {
		return $this->draw();
	}

	public function unserialize($data) {
		$xml = Xml::parseString($data);

		if ($xml) {
			$this->name = $xml->name;
			$this->contents = $xml->contents;
			$this->attributes = $xml->attributes;
		}
	}

	public function __toString() {
		return $this->draw();
	}
}
