<?php
/**
 * Manage SEO
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
namespace Solire\Lib;

/**
 * Manage SEO
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Seo
{

    /**
     *
     * @var string  Marker title
     */
    private $title;

    /**
     *
     * @var array Keywords of the page
     */
    private $keywords = [];

    /**
     *
     * @var string  Description of the page
     */
    private $description = '';

    /**
     *
     * @var string  Url canonical of the page
     */
    private $urlCanonical = '';

    /**
     *
     * @var string  Author of page
     */
    private $author;

    /**
     *
     * @var string  Authorname of page
     */
    private $authorName;

    /**
     *
     * @var bool  indexation of the page
     */
    private $index = true;

    /**
     *
     * @var bool  follow of the page
     */
    private $follow = true;


    /**
     * Get Marker title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;

    }


    /**
     * Set Marker title
     *
     * @param string $title Marker title
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;

    }


    /**
     * Get Prefix index
     *
     * @return string
     */
    public function getIndex()
    {
        if ($this->index === true) {
            return '';
        } else {
            return 'no';
        }

    }


    /**
     * Get Prefix follow
     *
     * @return string
     */
    public function getFollow()
    {
        if ($this->follow === true) {
            return '';
        } else {
            return 'no';
        }

    }


    /**
     * Enable indexation of the page
     *
     * @return void
     */
    public function enableIndex()
    {
        $this->index = true;

    }


    /**
     * Enable follow of the page
     *
     * @return void
     */
    public function enableFollow()
    {
        $this->follow = true;

    }


    /**
     * Disable indexation of the page
     *
     * @return void
     */
    public function disableIndex()
    {
        $this->index = false;

    }


    /**
     * Disable follow of the page
     *
     * @return void
     */
    public function disableFollow()
    {
        $this->follow = false;

    }


    /**
     * Get the array of keywords of the page
     *
     * @return array
     */
    public function getKeywords()
    {
        return $this->keywords;

    }


    /**
     * Set the array of keywords of the page
     *
     * @param array $keywords Array of keywords
     *
     * @return void
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

    }


    /**
     * Get description of the page
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;

    }


    /**
     * Get url canonical of the page
     *
     * @return string
     */
    public function getUrlCanonical()
    {
        return $this->urlCanonical;

    }


    /**
     * Get author of the page
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;

    }

    /**
     * Get authorName of the page
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;

    }


    /**
     * Set description of the page
     *
     * @param string $description Description of the page
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;

    }


    /**
     * Set url canonical of the page
     *
     * @param string $urlCanonical Url canonical of the page
     *
     * @return void
     */
    public function setUrlCanonical($urlCanonical)
    {
        $this->urlCanonical = $urlCanonical;

    }


    /**
     * Set author of the page
     *
     * @param string $author Author of the page
     *
     * @return void
     */
    public function setAuthor($author)
    {
        $this->author = $author;

    }

    /**
     * Set authorName of the page
     *
     * @param string $authorName AuthorName of the page
     *
     * @return void
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;

    }


    /**
     * Add a keywords
     *
     * @param string $keyword A keyword
     *
     * @return void
     */
    public function addKeyword($keyword)
    {
        $this->keywords[] = $keyword;

    }


    /**
     * Get keywords in string
     *
     * @return string
     */
    public function showKeywords()
    {
        return implode(', ', $this->keywords);

    }
}
