<?php

declare(strict_types=1);
namespace AfiuCMS\Controllers\Admin;
use AfiuCMS\Core\AuditLogger;use AfiuCMS\Core\Auth;use AfiuCMS\Core\Flash;use AfiuCMS\Core\Gate;use AfiuCMS\Core\Http\Request;use AfiuCMS\Core\Http\Response;use AfiuCMS\Core\Settings;use AfiuCMS\Core\View;
final class SettingsController extends AdminController{
 public function __construct(View $v,Auth $a,Settings $s,private readonly Gate $gate,private readonly AuditLogger $audit){parent::__construct($v,$a,$s);} public function index():Response{if($this->gate->role()!=='administrator')return $this->forbidden();return $this->page('admin.settings.general',['values'=>$this->settings->all()]);}
 public function update(Request $r):Response{if($this->gate->role()!=='administrator')return $this->forbidden();$name=trim((string)$r->input('site_name'));if($name===''){Flash::put('error','Site name cannot be empty.');return Response::redirect('/admin/settings');}$fields=['site_name'=>mb_substr($name,0,190),'site_tagline'=>mb_substr(trim((string)$r->input('site_tagline')),0,255),'site_description'=>mb_substr(trim((string)$r->input('site_description')),0,320),'homepage_title'=>mb_substr(trim((string)$r->input('homepage_title')),0,255),'footer_text'=>mb_substr(trim((string)$r->input('footer_text')),0,255),'posts_per_page'=>(string)max(1,min(50,(int)$r->input('posts_per_page',10))),'search_engine_visibility'=>$r->input('search_engine_visibility')==='1'?'1':'0'];foreach($fields as $k=>$v)$this->settings->set($k,(string)$v);$this->audit->record('settings.updated','settings',null,array_keys($fields));Flash::put('success','Settings saved.');return Response::redirect('/admin/settings');}
}
