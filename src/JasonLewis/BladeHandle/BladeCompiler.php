<?php namespace JasonLewis\BladeHandle;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler as IlluminateBladeCompiler;

class BladeCompiler extends IlluminateBladeCompiler {

	/**
	 * Array of content tags.
	 * 
	 * @var array
	 */
	protected $contentTagsStack = array();

	/**
	 * Array of escaped content tags.
	 * 
	 * @var array
	 */
	protected $escapedTagsStack = array();

	/**
	 * Indicates if the compiler will revert the content tags for each view.
	 * 
	 * @var bool
	 */
	protected $revertContentTags = false;

	/**
	 * Create a new blade compiler instance.
	 * 
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $cachePath
	 * @return void
	 */
	public function __construct(Filesystem $files, $cachePath)
	{
		parent::__construct($files, $cachePath);

		$this->appendDefaultTags();
	}

	/**
	 * Append the blade compilers default tags to the tag stack.
	 * 
	 * @return void
	 */
	protected function appendDefaultTags()
	{
		$this->appendTagStack($this->contentTags[0], $this->contentTags[1]);

		$this->appendTagStack($this->escapedTags[0], $this->escapedTags[1], true);
	}

	/**
	 * Compile the default content tags.
	 * 
	 * @param  string  $value
	 * @return string
	 */
	protected function compileDefault($value)
	{
		$pattern = $this->createMatcher('default');

		if ($default = $this->matchContentTags($pattern, $value))
		{
			list($value, $tags) = $default;

			$this->setContentTagsFromArray($tags);
		}

		return $value;
	}

	/**
	 * Compile the escaped content tags.
	 * 
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEscaped($value)
	{
		$pattern = $this->createMatcher('escaped');

		if ($escaped = $this->matchContentTags($pattern, $value))
		{
			list($value, $tags) = $escaped;

			$this->setEscapedContentTagsFromArray($tags);
		}

		return $value;
	}

	/**
	 * Match the content tags in the value and return the modified value and
	 * array of tags to use.
	 * 
	 * @param  string  $pattern
	 * @param  string  $value
	 * @return array|bool
	 */
	protected function matchContentTags($pattern, $value)
	{
		if (preg_match($pattern, $value, $match))
		{
			list ($match, $blank, $tags) = $match;

			// Normalize the tags by stripping out the parts that aren't needed so we are left
			// with just the given tags.
			$tags = explode(',', trim($tags, '()'));

			$tags = array_map(function($v) { return trim(preg_replace('/[\'"]+/', '', $v)); }, $tags);

			$value = preg_replace('/'.preg_quote($match, '/').'/', '', $value, 1);

			return array($value, $tags);
		}

		return false;
	}

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileString($value)
	{
		// If the compiler is set to revert to the default content tags then we'll set
		// the content and escaped content tags back to the defaults.
		if ($this->revertContentTags)
		{
			$this->setContentTagsFromArray($this->defaultContentTags());

			$this->setEscapedContentTagsFromArray($this->defaultEscapedTags());
		}

		array_unshift($this->compilers, 'Default', 'Escaped');

		foreach ($this->compilers as $compiler)
		{
			$value = $this->{"compile{$compiler}"}($value);
		}

		return $value;
	}

	/**
	 * Returns the default content tags.
	 * 
	 * @return array
	 */
	protected function defaultContentTags()
	{
		return reset($this->contentTagsStack);
	}

	/**
	 * Returns the default escaped tags.
	 * 
	 * @return array
	 */
	protected function defaultEscapedTags()
	{
		return reset($this->escapedTagsStack);
	}

	/**
	 * Returns the parent content tags.
	 * 
	 * @return array
	 */
	protected function parentContentTags()
	{
		return end($this->contentTagsStack);
	}

	/**
	 * Returns the parent escaped tags.
	 * 
	 * @return array
	 */
	protected function parentEscapedTags()
	{
		return end($this->escapedTagsStack);
	}

	/**
	 * Append an opening and closing tag to the tags stack.
	 * 
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @param  bool  $escaped
	 * @return void
	 */
	protected function appendTagStack($openTag, $closeTag, $escaped = false)
	{
		$property = ($escaped === true) ? 'escapedTagsStack' : 'contentTagsStack';

		$this->{$property}[] = array($openTag, $closeTag);
	}

	/**
	 * Sets the escaped content tags from an array of tags.
	 * 
	 * @param  array  $tags
	 * @return void
	 */
	protected function setEscapedContentTagsFromArray(array $tags)
	{
		call_user_func_array(array($this, 'setEscapedContentTags'), $tags);
	}

	/**
	 * Sets the content tags from an array of tags.
	 * 
	 * @param  array  $tags
	 * @return void
	 */
	protected function setContentTagsFromArray(array $tags)
	{
		call_user_func_array(array($this, 'setContentTags'), $tags);
	}

	/**
	 * Sets the content tags used for the compiler.
	 *
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @param  bool    $escaped
	 * @return void
	 */
	public function setContentTags($openTag, $closeTag, $escaped = false)
	{
		parent::setContentTags($openTag, $closeTag, $escaped);

		$this->appendTagStack($openTag, $closeTag, $escaped);
	}

	/**
	 * Sets the compiler to revert to the default content tags.
	 * 
	 * @param  bool  $revert
	 * @return void
	 */
	public function revertContentTags($revert = true)
	{
		$this->revertContentTags = $revert;
	}

}