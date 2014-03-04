<?php
/*
 * Constants used exclusively by this class
 */
define("ELEMENT_NOT_FOUND_PARSER_MESSAGE",
    "No '%s' element was found while parsing the document");
define("EXPECTING_ONLY_HEAD_AND_BODY_PARSER_MESSAGE",
    "Expecting only head and body elements at top level, found '%d' elements.");

class PageParser {
    //public function __construct()

    public function parseDocumentFile($filepath) {
        $domDocument = new DOMDocument();
        $domDocument->loadXMLFile($filepath);

        $headElement = null;
        $bodyElement = null;

        $root = $domDocument->documentElement;
        $rootChildren = $root->childNodes;

        validateHeadAndBody($rootChildren);

        $headElement = $rootChildren->item(0);
        $bodyElement = $rootChildren->item(1);
    }

    private function validateHeadAndBody($rootChildren) {
        if ($rootChildren->length != 2) {
            throwInvalidError(EXPECTING_ONLY_HEAD_AND_BODY_PARSER_MESSAGE,
                $rootChildren->length);
        }
        if ($rootChildren->item(0)->tagName !== HEAD_HTML_TAG_NAME) {
            throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                HEAD_HTML_TAG_NAME);
        }
        if ($rootChildren->item(1)->tagName !== BODY_HTML_TAG_NAME) {
            throwInvalidError(ELEMENT_NOT_FOUND_PARSER_MESSAGE,
                BODY_HTML_TAG_NAME);
        }
    }

    private function throwInvalidError($message) {
        $formattedMessage = sprintf(func_get_args());
        throw new InvalidArgumentError($formattedMessage);
    }
}
?>
