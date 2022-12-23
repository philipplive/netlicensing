<?php
namespace HfCore;

/**
 * @method static HtmlNode a(HtmlNode|string ...$content)
 * @method static HtmlNode p(HtmlNode|string ...$content)
 * @method static HtmlNode br(HtmlNode|string ...$content)
 * @method static HtmlNode div(HtmlNode|string ...$content)
 * @method static HtmlNode img(HtmlNode|string ...$content)
 * @method static HtmlNode input(HtmlNode|string ...$content)
 * @method static HtmlNode select(HtmlNode|string ...$content)
 * @method static HtmlNode option(HtmlNode|string ...$content)
 * @method static HtmlNode textarea(HtmlNode|string ...$content)
 * @method static HtmlNode button(HtmlNode|string ...$content)
 * @method static HtmlNode section(HtmlNode|string ...$content)
 * @method static HtmlNode span(HtmlNode|string ...$content)
 * @method static HtmlNode label(HtmlNode|string ...$content)
 * @method static HtmlNode small(HtmlNode|string ...$content)
 * @method static HtmlNode strong(HtmlNode|string ...$content)
 * @method static HtmlNode li(HtmlNode|string ...$content)
 * @method static HtmlNode ul(HtmlNode|string ...$content)
 * @method static HtmlNode ol(HtmlNode|string ...$content)
 * @method static HtmlNode dl(HtmlNode|string ...$content)
 * @method static HtmlNode dt(HtmlNode|string ...$content)
 * @method static HtmlNode dd(HtmlNode|string ...$content)
 * @method static HtmlNode svg(HtmlNode|string ...$content)
 * @method static HtmlNode h1(HtmlNode|string ...$content)
 * @method static HtmlNode h2(HtmlNode|string ...$content)
 * @method static HtmlNode h3(HtmlNode|string ...$content)
 * @method static HtmlNode h4(HtmlNode|string ...$content)
 * @method static HtmlNode h5(HtmlNode|string ...$content)
 * @method static HtmlNode h6(HtmlNode|string ...$content)
 * @method static HtmlNode table(HtmlNode|string ...$content)
 * @method static HtmlNode tbody(HtmlNode|string ...$content)
 * @method static HtmlNode thead(HtmlNode|string ...$content)
 * @method static HtmlNode tfoot(HtmlNode|string ...$content)
 * @method static HtmlNode tr(HtmlNode|string ...$content)
 * @method static HtmlNode td(HtmlNode|string ...$content)
 * @method static HtmlNode th(HtmlNode|string ...$content)
 * @method static HtmlNode link(HtmlNode|string ...$content)
 * @method static HtmlNode meta(HtmlNode|string ...$content)
 * @method static HtmlNode nav(HtmlNode|string ...$content)
 * @method static HtmlNode article(HtmlNode|string ...$content)
 * @method static HtmlNode main(HtmlNode|string ...$content)
 * @method static HtmlNode time(HtmlNode|string ...$content)
 * @method static HtmlNode canvas(HtmlNode|string ...$content)
 * @method static HtmlNode video(HtmlNode|string ...$content)
 * @method static HtmlNode form(HtmlNode|string ...$content)
 * @method static HtmlNode fieldset(HtmlNode|string ...$content)
 * @method static HtmlNode legend(HtmlNode|string ...$content)
 * @method static HtmlNode figure(HtmlNode|string ...$content)
 * @method static HtmlNode figcaption(HtmlNode|string ...$content)
 * @method static HtmlNode address(HtmlNode|string ...$content)
 * @method static HtmlNode dialog(HtmlNode|string ...$content)
 */
class HtmlNode extends Xml {
	public static $collapse = ['br', 'img', 'input', 'hr', 'col', 'use', 'link', 'meta'];

	/**
	 * Klassen
	 * @var boolean[]
	 */
	public $classes = [];

	/**
	 * Styles
	 * @var array
	 */
	public $styles = [];

	/**
	 * Checksumme von Content als Data-Attribut hinzufügen
	 * @var boolean
	 */
	public $addContentChecksumDataAttribute = false;

	/**
	 * Checksumme von Content
	 * @var string
	 */
	public $contentChecksum = null;

	/**
	 * CSS Klasse hinzufügen (mehrere durch space getrennt)
	 * @param string|[] $class
	 * @return $this
	 */
	public function addClass(?string $class): self {
		if (!$class)
			return $this;

		foreach (is_array($class) ? $class : explode(' ', $class) as $class) {
			if ($class)
				$this->classes[$class] = true;
		}
		return $this;
	}

	/**
	 * CSS Klasse entfernen
	 * @param string|[] $class
	 * @return $this
	 */
	public function removeClass(?string $class): self {
		if (!$class)
			return $this;

		foreach (is_array($class) ? $class : explode(' ', $class) as $class) {
			if ($class)
				unset($this->classes[$class]);
		}
		return $this;
	}

	/**
	 * Klasse existiert?
	 * @param string $class
	 * @return boolean
	 */
	public function hasClass(string $class): bool {
		return isset($this->classes[$class]);
	}

	/**
	 * Klassen auslesen
	 * @return array
	 */
	public function getClasses(): array {
		return array_keys($this->classes);
	}

	/**
	 * Style Eigenschaft setzen
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setStyle(string $name, $value): self {
		$name = trim(strtolower($name));
		$this->styles[$name] = trim($value);
		return $this;
	}

	/**
	 * Style Eigenschaft auslesen
	 * @param string $name
	 * @return string|null
	 */
	public function getStyle(string $name) {
		$name = trim(strtolower($name));
		return isset($this->styles[$name]) ? $this->styles[$name] : null;
	}

	/**
	 * Hintergrundbild setzen
	 * @param string $path
	 * @param string $emptyClass
	 * @return $this
	 */
	public function setBackgroundImage(?string $path = null, ?string $emptyClass = null): self {
		if ($emptyClass) {
			if ($path)
				$this->removeClass($emptyClass);
			else
				$this->addClass($emptyClass);
		}
		return $this->setStyle('background-image', $path ? sprintf("url('%s')", $path) : null);
	}

	/**
	 * Style setzen
	 * @param string $style
	 * @return $this
	 */
	public function style(?string $style = null): self {
		$this->styles = [];
		if (is_array($style)) {
			foreach ($style as $name => $value)
				$this->setStyle($name, $value);
		}
		else {
			foreach (explode(';', $style) as $rule) {
				$rule = trim($rule);
				if (!$rule)
					continue;
				$data = explode(':', $rule);
				$this->setStyle($data[0], $data[1]);
			}
		}
		return $this;
	}

	/**
	 * Node per Style ausblenden
	 * @return $this
	 */
	public function hide(): self {
		return $this->style('display:none;');
	}

	/**
	 * Clear-Div hinzufügen
	 * @return $this
	 */
	public function floatClear(): self {
		$this->append(HtmlNode::div()->addClass('clear'));
		return $this;
	}

	/**
	 * Inner Html setzen
	 * @param string|null $html
	 * @return $this
	 */
	public function html(?string $html): self {
		$this->contents = [$html];
		return $this;
	}

	/**
	 * Value setzen
	 * @param string $value
	 * @return $this
	 */
	public function value($value = null): self {
		$this->attr('value', $value);
		return $this;
	}

	/**
	 * Id setzen
	 * @param string $id
	 * @return $this
	 */
	public function id(string $id = null): self {
		$this->attr('id', $id);
		return $this;
	}

	/**
	 * Data-Attribut setzen
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function data(string $name, $value = null): self {
		$this->attr('data-'.$name, $value);
		return $this;
	}

	/**
	 * Data-Attribut auslesen
	 * @param string $name
	 * @return mixed
	 */
	public function getData(string $name) {
		return $this->getAttr('data-'.$name);
	}

	/**
	 * ChecksumAttribut hinzufügen
	 * @param boolean $addContentChecksumDataAttribute
	 * @return $this
	 */
	public function addContentChecksumDataAttribute(bool $addContentChecksumDataAttribute): self {
		$this->addContentChecksumDataAttribute = $addContentChecksumDataAttribute;
		return $this;
	}

	/**
	 * Rendern
	 * @return string
	 */
	public function draw(): string {
		$contents = '';
		foreach ($this->contents as $content) {
			if ($content instanceof self)
				$contents .= $content->draw();
			else
				$contents .= (string)$content;
		}

		if ($this->addContentChecksumDataAttribute) {
			$this->contentChecksum = md5($contents);
			$this->data('content-checksum', $this->contentChecksum);
		}

		$output = '';
		if ($this->name) {
			$output .= '<'.$this->name;
			if (count($this->classes))
				$this->attr('class', implode(' ', $this->getClasses()));

			if (count($this->styles)) {
				$rules = [];
				foreach ($this->styles as $name => $value) {
					if ($value !== null)
						$rules[] = $name.':'.$value.';';
				}
				if (count($rules))
					$this->attr('style', implode('', $rules));
			}

			if (count($this->attributes)) {
				foreach ($this->attributes as $name => $value)
					$output .= ' '.$name.'="'.$value.'"';
			}
			// Collapsed Tag wie <br/> etc.
			if (in_array($this->name, self::$collapse)) {
				$output .= ' />';
				return $output;
			}

			$output .= '>';
		}
		$output .= $contents;

		if ($this->name)
			$output .= '</'.$this->name.'>';

		return $output;
	}

	/**
	 * Specialchars escape und Linebreak
	 * @param string $str
	 * @return string
	 */
	protected function escape(?string $str): ?string {
		$str = parent::escape($str);
		$str = nl2br($str);
		return $str;
	}

	/**
	 * Unescape und Linebreaks entfernen
	 * @param string $str
	 * @return string
	 */
	protected function unescape(?string $str): ?string {
		$str = str_replace('<br />', '', $str);
		$str = parent::unescape($str);
		return $str;
	}

	/**
	 * Gibt einen neuen Html node erzeugen
	 * @param string $name
	 * @param HtmlNode|string $arguments
	 * @return self
	 */
	public static function __callStatic(string $name, $arguments) {
		$node = self::create($name);

		foreach ($arguments as $argument) {
			if ($argument instanceof self)
				$node->append($argument);
			else
				$node->appendText($argument);
		}

		return $node;
	}

	/**
	 * Neuer Node
	 * @param string|null $name
	 * @return self
	 */
	public static function create(?string $name = null) {
		return new self($name);
	}

	public function __toString() {
		return $this->draw();
	}

}
