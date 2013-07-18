<?php
/**
 * Generates a string representation from a breadcrumb.
 * The breadcrumb is used to track the position when recursively
 * parsing templates in a wikipage.
 * The tracking is needed to associate child objects with their parent
 * object.
 *
 * Basically only ignores the first node, as values from templates on
 * wiki-page level are added directly to the wiki page.
 *
 * @param <type> $breadcrumb
 */
class DefaultBreadCrumbTransformer
    implements IBreadCrumbTransformer
{
    public function transform(BreadCrumb $breadCrumb)
    {
        $result = $breadCrumb->getRoot();

        $isFirst = true;
        foreach($breadCrumb->getNodes() as $node) {
            if($isFirst) {
                $isFirst = false;

                if($breadCrumb->getDepth() > 1)
                    $result .= "/" . $node->getPropertyName();

                continue;
            }

            $result .= "/" . $node->getTemplateName();
            if($node->getTemplateIndex() != 0)
                $result .= "/" . $node->getTemplateIndex();
        }

        return $result;
    }
}