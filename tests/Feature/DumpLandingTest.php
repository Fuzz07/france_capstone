<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DumpLandingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dump_diag()
    {
        // Served from Apache's docroot, so absolute /images/ paths resolve as normal.
        $html = $this->get('/')->getContent();

        $probe = <<<'HTML'
<style>.gallery-modal-overlay{opacity:1!important;visibility:visible!important;pointer-events:auto!important;}
.offline-overlay{display:none!important;}body.offline-blocked{overflow:auto!important;}
#diag{position:fixed;left:0;right:0;bottom:0;z-index:99999999;background:#020617;color:#7dd3fc;
font:12px/1.55 monospace;padding:10px;white-space:pre-wrap;max-height:46vh;overflow:auto}</style>
<script>
window.addEventListener('load', function () {
  var out = [];
  var v = document.getElementById('galleryMainVideo');
  v.addEventListener('error', function(){ out.push('EVENT error code=' + (v.error?v.error.code:'?')); });
  ['loadstart','loadedmetadata','canplay','playing','stalled','waiting','suspend'].forEach(function(e){
    v.addEventListener(e, function(){ out.push('EVENT ' + e); });
  });

  document.getElementById('heroImageBtn').click();
  out.push('hero clicked; typeof openGallery=' + (typeof openGallery));

  setTimeout(function () {
    var NS=['EMPTY','IDLE','LOADING','NO_SOURCE'];
    var RS=['HAVE_NOTHING','HAVE_METADATA','HAVE_CURRENT_DATA','HAVE_FUTURE_DATA','HAVE_ENOUGH_DATA'];
    var s = document.getElementById('galleryVideoStatus');
    out.push('--- state ---');
    out.push('currentSrc   = ' + (v.currentSrc||'(none)'));
    out.push('display      = ' + getComputedStyle(v).display + '  hidden=' + v.hidden);
    out.push('networkState = ' + v.networkState + ' (' + NS[v.networkState] + ')');
    out.push('readyState   = ' + v.readyState + ' (' + RS[v.readyState] + ')');
    out.push('paused=' + v.paused + '  muted=' + v.muted + '  t=' + v.currentTime.toFixed(2) + '  dur=' + v.duration);
    out.push('buffered     = ' + (v.buffered.length ? v.buffered.end(0).toFixed(2)+'s' : 'nothing'));
    out.push('error        = ' + (v.error ? 'code '+v.error.code : 'none'));
    out.push('status shown = ' + (s ? !s.hidden : 'n/a') + (s && !s.hidden ? '  "'+s.textContent+'"' : ''));
    out.push('--- can the play button be clicked? ---');
    var r = v.getBoundingClientRect();
    var hit = document.elementFromPoint(r.left+24, r.bottom-22);
    out.push('elementFromPoint(playBtn) = <' + (hit?hit.tagName.toLowerCase():'null') + ' class="' + (hit&&hit.className?hit.className:'') + '">');
    var d=document.createElement('div'); d.id='diag'; d.textContent=out.join('\n'); document.body.appendChild(d);
  }, 6000);
});
</script>
HTML;

        file_put_contents(public_path('_diag.html'), str_replace('</body>', $probe . '</body>', $html));
        $this->assertFileExists(public_path('_diag.html'));
    }
}
