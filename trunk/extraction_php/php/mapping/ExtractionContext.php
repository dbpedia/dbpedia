<?php
namespace dbpedia\mapping;
{
class ExtractionContext {
    private $destinations;
    private $redirects;

    public function setDestinations($destinations)
    {
        $this->destinations = $destinations;
    }

    public function getDestinations()
    {
        return $this->destinations;
    }

    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;
    }

    public function getRedirects()
    {
        return $this->redirects;
    }
}
}