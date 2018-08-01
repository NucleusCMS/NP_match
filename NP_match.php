<?php 
class NP_match extends NucleusPlugin { 
	function getName() { return 'NP_match'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.2.5'; }
	function getURL() {return 'http://japan.nucleuscms.org/wiki/plugins:np_match';}
	function getDescription() { return $this->getName().' plugin. <br /> Usage: &lt;%if(match,name=str)%&gt; etc.'; } 
	function supportsFeature($what) { return ($what=='SqlTablePrefix')?1:0; }
	var $conf=array('method'=>'str', 'decode'=>'', 'data'=>'');
	var $securemode=false;
	function doSkinVar($skintype,$p1='',$p2=''){
		switch($p1){
		case 'default': $this->conf=$this->setting($p2); break;
		default: break;
		}
	}
	function doIf($p1,$p2=''){
		global $HTTP_GET_VARS,$HTTP_SERVER_VARS;
		if (!is_array($_GET)) $_GET=&$HTTP_GET_VARS;
		if (!is_array($_SERVER)) $_SERVER=&$HTTP_SERVER_VARS;
		// Initialize
		if (!preg_match('/^([^<>=!]+)([<>=!][<>=]?)([\S\s]*)$/',$p1,$matches)) return $this->_error('Syntax error:'.$p1);
		list($p1,$name,$comp,$value)=$matches;
		if (!is_array(  $matchconf=$this->setting($p2)  )) return false;
		// Origin of data (get $var from data)
		switch($matchconf['data']){
		case 'get':
			if (isset($_GET[$name]))    $var=$_GET[$name];
			break;
		case 'globals':
			if (isset($GLOBALS[$name])) $var=$GLOBALS[$name];
			break;
		case 'server':
			if (isset($_SERVER[$name])) $var=$_SERVER[$name];
			break;
		default:
			if     (isset($_SERVER[$name])) $var=$_SERVER[$name];
			elseif (isset($GLOBALS[$name])) $var=$GLOBALS[$name];
			elseif (isset($_GET[$name]))    $var=$_GET[$name];
		}
		if ($matchconf['method']=='isset') return isset($var);
		if (!isset($var)) $var=='';
		// Decode the regular expression or not (convert $value that is encoded regular expression)
		switch($matchconf['decode']){
			case 'rawurldecode': case 'urldecode':
				$value=call_user_func($matchconf['decode'],$value);
			default: break;
		}
		// Try matching
		switch($matchconf['method']){
		case 'preg': case 'preg_match': case 'ereg': case 'eregi':
			return $this->_regexp($matchconf['method'],$value,$var);
		case 'int': case 'integer':
			list($var,$var2)=array( (int)$var, (int)$value );
			break;
		case 'float': case 'double': case 'real':
			list($var,$var2)=array( (float)$var, (float)$value );
			break;
		default: case 'str': case 'string':
			list($var,$var2)=array( (string)$var, (string)$value );
			break;
		}
		switch($comp){
			case '=' : case '==': return ($var == $var2);
			case '!=': case '<>': return ($var != $var2);
			case '<=': case '=<': return ($var <= $var2);
			case '>=': case '=>': return ($var >= $var2);
			case '<' :            return ($var <  $var2);
			case '>' :            return ($var >  $var2);
			default: return $this->_error('Syntax error:'.$p1.' near "'.$comp.'"');
		}
	}
	function _regexp($method,$value,$var){
		$orgerr=error_reporting( E_ALL ^ E_NOTICE );
		ob_start();
		$ret=call_user_func(($method=='preg'?'preg_match':$method),$value,$var);
		$err=ob_get_contents();
		ob_end_clean();
		error_reporting($orgerr);
		if ($err) return $this->_error('Regular expression error: "'.$value.'"');
		return (bool)$ret;
	}
	function setting($value){
		if (!$value) return $this->conf;
		$matchconf=$this->conf;
		foreach(explode('|',$value) as $each) if ($each) {
			switch (count(  $each=explode('=',$each)  )) {
				case 1: $matchconf['method']=$each[0]; break;
				case 2: $matchconf[$each[0]]=$each[1]; break;
				default: return $this->_error('Syntax error: near "'.$each[0].'"');
			}
		}
		return $matchconf;
	}
	function _error($msg){
		echo '<!-- --><b>'.$this->getName().' - '.htmlspecialchars($msg,ENT_QUOTES).'</b>';
		if ($this->securemode) exit;
		return false;
	}
}
?>
