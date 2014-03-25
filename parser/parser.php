<?php
//TODO dynamically load the class php files
require_once("classfileloader.php");
require_once("/lib/util/util.php");
require_once("/lib/elements/HtmlElement.php");
require_once("/lib/elements/Widget.php");
require_once("tagnames.php");
require_once("elementParser.php");
/*
 * Constants used exclusively by this class
 */
define("ELEMENT_NOT_FOUND_PARSER_MESSAGE",
    "No '%s' element was found while parsing the document");
define("EXPECTING_ONLY_HEAD_AND_BODY_PARSER_MESSAGE",
    "Expecting only head and body elements in html tag, found '%d' elements.");
define("EXPECTING_ONLY_META_AND_HTML_PARSER_MESSAGE",
    "Expecting only pml:meta and html tags at top level, found '%d' elements.");
define("ELEMENT_MISSING_ATTRIBUTE_PARSER_MESSAGE",
    "'%s' element is missing '%s' attribute.");
define("UNEXPECTED_TYPE_PARSER_MESSAGE",
    "Expected '%s' but received '%s'");
define("DUPLICATE_MANAGED_ID_PARSER_MESSAGE",
    "The managed_element_id '%s' has already been used.");
//TODO move this to a settings file
define("DEFAULT_ELEMENT_PARSER", "ElementParser");

class PageParser {
    private $managedDocument;
    private $elementParser;

    public function __construct() {
        $elementParserClassName = DEFAULT_ELEMENT_PARSER;
        $this->elementParser = new $elementParserClassName();
    }

    public function parseDocumentFile($filepath) {
        $domDocument = new DOMDocument();
        $domDocument->preserveWhiteSpace = false;
        $domDocument->load($filepath);

        $root = $domDocument->documentElement;
        $rootChildren = $root->childNodes;

        $this->validateMetaAndHtml($rootChildren);

        $metaElement = $rootChildren->item(0);
        $htmlChildren = $rootChildren->item(1)->childNodes;

        $this->validateHeadAndBody($htmlChildren);

        $htmlHead = $htmlChildren->item(0);
        $htmlBody = $htmlChildren->item(1);

        $this->parseMetaSection($metaElement);

        $parsedHead = $this->elementParser->parseElement($htmlHead,
            $this->managedDocument);
        $parsedBody = $this->elementParser->parseElement($htmlBody,
            $this->managedDocument);

        $this->managedDocument->headElements = $parsedHead->childElements;
        $this->managedDocument->bodyElements = $parsedBody->childElements;

        $managedElements = $this->elementParser->getManagedElements();
        foreach($managedElements as $managedElement) {
            if ($managedElement instanceof ManagedElement) {
                $managedElement->onDOMLoad($this->managedDocument);
            }
        }
        $this->managedDocument->onDOMLoad($managedElements);

        //write the document
        $this->managedDocument->writeDocument();
    }

    private function validateMetaAndHtml($rootChildren) {
        if ($rootChildren->length != 2) {
            $PageParser::throwInvalidError(EXPECTING_ONLY_META_AND_HTML_PARSER_MESSAGE,
                $rootChildren->length);
        }
        if (!equalsIgnoreCase($rootChildren->item(0)->tagName,
                PML_META_TAG_NAME)) {
            $PageParser::throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                PML_META_TAG_NAME);
        }
        if (!equalsIgnoreCase($rootChildren->item(1)->tagName, HTML_TAG_NAME)) {
            $PageParser::throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                HTML_TAG_NAME);
        }
    }

    private function validateHeadAndBody($htmlChildren) {
        if ($htmlChildren->length != 2) {
            $PageParser::throwInvalidError(EXPECTING_ONLY_HEAD_AND_BODY_PARSER_MESSAGE,
                $htmlChildren->length);
        }
        if (!equalsIgnoreCase($htmlChildren->item(0)->tagName,
                HEAD_HTML_TAG_NAME)) {
            $PageParser::throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                HEAD_HTML_TAG_NAME);
        }
        if (!equalsIgnoreCase($htmlChildren->item(1)->tagName,
                BODY_HTML_TAG_NAME)) {
            $PageParser::throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                BODY_HTML_TAG_NAME);
        }
    }

    private function parseMetaSection($metaElement) {
        $metaChildren = $metaElement->childNodes;

        $managedDocumentElement = null;
        foreach($metaChildren as $element) {
            if ($element instanceof DOMElement
                    && equalsIgnoreCase($element->tagName,
                    PML_MANAGED_DOCUMENT_TAG_NAME)) {
                $managedDocumentElement = $element;
            }
        }

        if ($managedDocumentElement == null) {
            $PageParser::throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                PML_MANAGED_DOCUMENT_TAG_NAME);
        } else {
            $this->createDocumentFromElement($managedDocumentElement);
        }
    }

    private function createDocumentFromElement($managedDocumentElement) {
        $docClassName = $this->getAttributeValue($managedDocumentElement,
            PML_MANAGED_DOCUMENT_CLASS_NAME_ATTRIBUTE);
//TODO dynamically load the class php files
//        $docFilePath = $this->getAttributeValue($managedDocumentElement,
//            PML_MANAGED_DOCUMENT_FILE_PATH_ATTRIBUTE);
        $this->managedDocument = new $docClassName();
    }

    private function getAttributeValue($element, $attributeName) {
        if ($element->hasAttribute($attributeName)) {
            return $element->getAttribute($attributeName);
        } else {
            PageParser::throwInvalidError(ELEMENT_MISSING_ATTRIBUTE_PARSER_MESSAGE,
                $element, $attributeName);
        }
    }

    /*
     * TODO make this actually return different values based on class name
     */
    public static function getNewElementParser($elementClassName) {
        $elementParserClassName = DEFAULT_ELEMENT_PARSER;
        return new $elementParserClassName();
    }

    public static function throwInvalidError($message) {
        $formattedMessage = call_user_func_array("sprintf", func_get_args());
        throw new InvalidArgumentException($formattedMessage);
    }
}
?>
