<?php
/**
 * Add the `replaceWholeText()` method. To the text node classes.
 *
 * https://www.w3.org/TR/DOM-Level-3-Core/core.html#Text3-replaceWholeText
 *
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @copyright Copyright (c) 2009-2016 Bastian Feder, Thomas Weinert
 */

namespace FluentDOM\Node {

  use FluentDOM\Document;

  if (!defined('LIBXML_NO_MODIFICATION_ALLOWED_ERR')) {
    define('LIBXML_NO_MODIFICATION_ALLOWED_ERR',  7);
  }

  /**
   * Add the `replaceWholeText()` method. To the text node classes.
   *
   * https://www.w3.org/TR/DOM-Level-3-Core/core.html#Text3-replaceWholeText
   *
   * @property Document $ownerDocument
   * @property \DOMNode $previousSibling
   * @property \DOMNode $nextSibling
   * @property \DOMNode $parentNode
   */
  trait WholeText {

    /**
     * @param string $text
     * @return $this|NULL
     */
    public function replaceWholeText($text) {
      /** @var \FluentDOM\Text|\FluentDOM\CdataSection $this */
      $text = (string)$text;
      $canReplaceEntity = function(\DOMEntityReference $reference) use (&$canReplaceEntity) {
        if (
          $reference->firstChild instanceof \DOMEntity &&
          $reference->firstChild->hasChildNodes()
        ) {
          foreach ($reference->firstChild->childNodes as $childNode) {
            $canReplace = FALSE;
            if ($childNode instanceof \DOMEntityReference) {
              $canReplace = $canReplaceEntity($childNode);
            } elseif (
              $childNode instanceof \DOMCharacterData
            ) {
              $canReplace = TRUE;
            }
            if (!$canReplace) {
              return FALSE;
            }
          }
          return TRUE;
        } else {
          return FALSE;
        }
      };
      $replaceNode = function(\DOMNode $node = NULL) use ($canReplaceEntity) {
        if (
          $node instanceof \DOMElement ||
          $node instanceof \DOMComment ||
          $node instanceof \DOMProcessingInstruction
        ) {
          return FALSE;
        } elseif ($node instanceof \DOMNode && $node->parentNode instanceof \DOMNode) {
          if (
            $node instanceof \DOMEntityReference &&
            !$canReplaceEntity($node)
          ) {
            throw new \DOMException(LIBXML_NO_MODIFICATION_ALLOWED_ERR);
          }
          $node->parentNode->removeChild($node);
          return TRUE;
        }
        return FALSE;
      };
      do {
        $previous = $this->previousSibling;
      } while ($replaceNode($previous));
      do {
        $next = $this->nextSibling;
      } while ($replaceNode($next));
      if ($text === '') {
        if ($this->parentNode instanceof \DOMNode) {
          $this->parentNode->removeChild($this);
        }
        $this->textContent = '';
        return NULL;
      } else {
        $this->textContent = $text;
        return $this;
      }
    }
  }
}