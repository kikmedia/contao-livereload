<?php

/**
 * Contao-Livereload
 * A CSS livereload tool for Contao
 *
 * @copyright  4ward.media 2014 <http://www.4wardmedia.de>
 * @author     Christoph Wiechert <wio@psitrax.de>
 * @package    contao-livereload
 * @license    LGPL
 * @filesource
 */
 
class ContaoLivereload
{

    public function addJscript($strBuffer, $strTemplate)
    {
        if(!preg_match("~^fe_page.*~", $strTemplate)) return $strBuffer;

        if(!$_COOKIE['BE_USER_AUTH']) return $strBuffer;

        $DB = \Database::getInstance();
        $Session = $DB->prepare('SELECT pid FROM tl_session WHERE name="BE_USER_AUTH" AND hash=?')->limit(1)->execute($_COOKIE['BE_USER_AUTH']);
        if(!$Session->numRows) return $strBuffer;
        $User = $DB->prepare('SELECT contaoLivereload_enabled, contaoLivereload_server FROM tl_user WHERE id=?')->execute($Session->pid);

        if(!$User->contaoLivereload_enabled) return $strBuffer;
        $srv = $User->contaoLivereload_server;
        $arrCombined = array();

        foreach((array)$GLOBALS['TL_FRAMEWORK_CSS'] as $f) {
            $arrCombined[] = $f;
        }

        foreach((array)$GLOBALS['TL_CSS'] as $f) {
            $f = explode('|', $f);
            if($f[2] && $f[2] == 'static') $arrCombined[] = $f[0];
        }

        foreach((array)$GLOBALS['TL_USER_CSS'] as $f) {
            $f = explode('|', $f);
            if($f[2] && $f[2] == 'static') {
                $arrCombined[] = $f[0];
            }
        }


        $str = '<script type="application/json" id="contao-livereload-files">'.json_encode($arrCombined).'</script>';
        $str .= '<script type="text/javascript" src="'.$srv.':35729/livereload.js"></script>';
        $json = json_encode($arrCombined);
        $str .= <<<JAVASCRIPT
<script type="text/javascript">
(function($) { $(document).ready(function() {
    var cfiles = {$json};
    var cdest = '';
    var nfiles = [];
    $('link[rel=stylesheet][href]').each(function() {
      var href = $(this).attr('href');
      if(href.match(/^http.?:/)) return;
      if(href.substr(0, "assets/css/".length) === "assets/css/") {
        cdest = href;
      } else {
        nfiles.push(href);
      }
    });

    $.ajax({
      type: "POST",
      url: '{$srv}:35720',
      processData: false,
      contentType: 'application/json',
      data: JSON.stringify({cfiles: cfiles, cdest: cdest, nfiles: nfiles}),
      dataType: 'json'
    });
}); })(jQuery);
</script>
JAVASCRIPT;


        $strBuffer = str_replace(
            '</body>',
            $str.'</body>',
            $strBuffer);

        return $strBuffer;
    }
}