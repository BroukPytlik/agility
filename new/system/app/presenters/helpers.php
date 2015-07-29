<?php
namespace Agility;
/*
 * My helpers
 */

class Helpers extends \Nette\Object
{
    private $context;


    public function __construct($context = NULL)
    {
        $this->context = $context;
    }

    public function loader($helper)
    {
        if (method_exists($this, $helper)) {
            return callback($this, $helper);
        }
    }

/*    public function helperName($arg)
    {
        return $this->context->model->fce($arg);
    }*/
    /** 
     * convert http://... to  <a>
     */
    public function linkify($s) {
	return preg_replace('/(?<!\*)(?<!\*http:\/\/)(?<!\*https:\/\/)(?<!\*ftp:\/\/)(?<!\*ftps:\/\/)((((https?|ftps?):\/\/)|(www\.))(\S+(\.\S+)?)*([^\s,?!.]))(?!\*)/i', '<a href="$1">$1</a>', $s);
    }
    /** 
     * shortify long texts in <a>
     */
    public function shortify($s) {
	//dump(preg_replace('/(<a[^>]*>)((?:(?!<\/a>)).{10})(?:(?!<\/a>).*)((?!<\/a>).{8})(<\/a>)/U', '*$1$2...$3$4*', $s));
	return preg_replace('/(<a[^>]*>)(?:https?:\/\/)((?:(?!<\/a>)).{15})(?:(?!<\/a>).*)((?!<\/a>).{8})(<\/a>)/U', '$1$2 ... $3$4', $s);
    }
    /** 
     * convert $url$text$ syntax to <a>
     */
    public  function parseLinks($s) {
	// at first find $ links with http/ftp
	$text = preg_replace('/\*([^*]+)\*((https?|ftps?):\/\/([^ *]+))\*/U', '<a href="$2">$1</a>', $s);
	// and then also without
	return preg_replace('/\*([^*]+)\*([^ *]+)\*/U', '<a href="http://$2">$1</a>', $text);
    }
    /** strip all text within any tag
     */
    public function stripTags($s) {
	return preg_replace('/<[^>]*>[^<]*<[^>]*>/','',$s);
    }
    /** strip all URLs from text
     */
    public function stripUrls($s) {
	//return preg_replace('/<[^>]*>[^<]*<[^>]*>/','',$s);
	return preg_replace('/((((https?|ftps?):\/\/)|(www\.))(\S+\.\S+)*([^\s,?!.]))/i','',$s);
    }
    
    /** change < and > to &lt; and &gt;
     */
    public function escape($s){
	return str_replace('>','&gt;',str_replace('<', '&lt;', $s));
		
    }
    
    /** change  &lt; and &gt; to < and > 
     */
    public function unescape($s){
	return str_replace('&gt;','>',str_replace('&lt;', '<', str_replace('&amp;', '&', $s)));
		
    }
}