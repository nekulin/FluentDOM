<?php

namespace FluentDOM\Transformer {

  use FluentDOM\Document;
  use FluentDOM\Element;
  use FluentDOM\Xpath;

  class JSONx {

    const XMLNS_JSONX = 'http://www.ibm.com/xmlns/prod/2009/jsonx';
    const XMLNS_JSONDOM = 'urn:carica-json-dom.2013';

    /**
     * @var \DOMDocument
     */
    private $_document = NULL;

    /**
     * Import a DOM document and use the JsonDOM rules to convert it into JSONx.
     *
     * @param \DOMDocument $document
     */
    public function __construct(\DOMDocument $document) {
      $this->_document = $document;
    }

    /**
     * Create a JSONX document and return it as xml string
     *
     * @return string
     */
    public function __toString() {
      return $this->getDocument()->saveXml();
    }

    /**
     * Create and return a JSONx document.
     *
     * @return Document
     */
    public function getDocument() {
      $document = new Document();
      $document->registerNamespace('json', self::XMLNS_JSONX);
      $this->addNode($document, $this->_document->documentElement);
      return $document;
    }

    /**
     * @param Document|Element $parent
     * @param \DOMElement $node
     * @param bool $addNameAttribute
     */
    public function addNode($parent, \DOMElement $node, $addNameAttribute = FALSE) {
      switch ($this->getType($node)) {
      case 'object' :
        $result = $parent->appendElement('json:object');
        $this->appendChildNodes($result, $node, TRUE);
        break;
      case 'array' :
        $result = $parent->appendElement('json:array');
        $this->appendChildNodes($result, $node, FALSE);
        break;
      case 'number' :
        $result = $parent->appendElement('json:number', $node->nodeValue);
        break;
      case 'boolean' :
        $result = $parent->appendElement('json:boolean', $node->nodeValue);
        break;
      case 'null' :
        $result = $parent->appendElement('json:null');
        break;
      default :
        $result = $parent->appendElement('json:string', $node->nodeValue);
        break;
      }
      if ($addNameAttribute) {
        $name = $node->localName;
        if ($node->hasAttributeNS(self::XMLNS_JSONDOM, 'name')) {
          $name = $node->getAttributeNS(self::XMLNS_JSONDOM, 'name');
        }
        $result['name'] = $name;
      }
    }

    /**
     * @param \DOMElement $target
     * @param \DOMElement $source
     * @param bool $addNameAttribute
     */
    private function appendChildNodes(\DOMElement $target, \DOMElement $source, $addNameAttribute = FALSE) {
      $xpath = new Xpath($source->ownerDocument);
      /** @var \DOMElement $child */
      foreach ($xpath('*', $source) as $child) {
        $this->addNode($target, $child, $addNameAttribute);
      }
    }

    /**
     * @param \DOMElement $node
     * @return string
     */
    private function getType(\DOMElement $node) {
      if ($node->hasAttributeNS(self::XMLNS_JSONDOM, 'type')) {
        return $node->getAttributeNS(self::XMLNS_JSONDOM, 'type');
      } else {
        $xpath = new Xpath($node->ownerDocument);
        return $xpath('count(*) > 0', $node) ? 'object' : 'string';
      }
    }
  }
}