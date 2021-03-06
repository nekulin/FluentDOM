<?php
/**
 * Load a DOM document from a xml string
 *
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @copyright Copyright (c) 2009-2014 Bastian Feder, Thomas Weinert
 */

namespace FluentDOM\Loader {

  use FluentDOM\Document;
  use FluentDOM\DocumentFragment;
  use FluentDOM\Loadable;
  use FluentDOM\Loaders;
  use Hal\Component\Config\Loader;

  /**
   * Load a DOM document from a xml string
   */
  class Html implements Loadable {

    use Supports;

    const IS_FRAGMENT = 'fragment';
    const LIBXML_OPTIONS = 'libxml';

    /**
     * @return string[]
     */
    public function getSupported() {
      return array('html', 'text/html', 'html-fragment', 'text/html-fragment');
    }

    /**
     * @see Loadable::load
     * @param string $source
     * @param string $contentType
     * @param array $options
     * @return Document|Result|NULL
     */
    public function load($source, $contentType, array $options = []) {
      if ($this->supports($contentType)) {
        $selection = false;
        $document = new Document();
        $errorSetting = libxml_use_internal_errors(TRUE);
        $loadOptions = isset($options[self::LIBXML_OPTIONS]) ? (int)$options[self::LIBXML_OPTIONS] : 0;
        if ($this->isFragment($contentType, $options)) {
          $this->loadFragmentIntoDom($document, $source, $loadOptions);
          $selection = $document->evaluate('/*');
        } else if ($this->startsWith($source, '<')) {
          $document->loadHTML($source, $loadOptions);
        } else {
          $document->loadHTMLFile($source, $loadOptions);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($errorSetting);
        return new Result($document, 'text/html', $selection);
      }
      return NULL;
    }

    /**
     * @see LoadableFragment::loadFragment
     * @param string $source
     * @param string $contentType
     * @param array $options
     * @return DocumentFragment|NULL
     */
    public function loadFragment($source, $contentType, array $options = []) {
      if ($this->supports($contentType)) {
        $htmlDom = new Document();
        $errorSetting = libxml_use_internal_errors(TRUE);
        $loadOptions = isset($options[self::LIBXML_OPTIONS]) ? (int)$options[self::LIBXML_OPTIONS] : 0;
        $htmlDom->loadHtml('<html-fragment>'.$source.'</html-fragment>', $loadOptions);
        $nodes = $htmlDom->evaluate('//html-fragment[1]/node()');
        $fragment = $htmlDom->createDocumentFragment();
        foreach ($nodes as $node) {
          $fragment->appendChild($node);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($errorSetting);
        return $fragment;
      }
      return NULL;
    }

    private function isFragment($contentType, $options) {
      return (
        $contentType == 'html-fragment' ||
        $contentType == 'text/html-fragment' ||
        (isset($options[self::IS_FRAGMENT]) && $options[self::IS_FRAGMENT])
      );
    }

    private function loadFragmentIntoDom($dom, $source, $loadOptions) {
      $htmlDom = new Document();
      $htmlDom->loadHtml('<html-fragment>'.$source.'</html-fragment>', $loadOptions);
      $nodes = $htmlDom->evaluate('//html-fragment[1]/node()');
      foreach ($nodes as $node) {
        $dom->appendChild($dom->importNode($node, TRUE));
      }
    }
  }
}