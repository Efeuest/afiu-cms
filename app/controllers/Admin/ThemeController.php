<?php

declare(strict_types=1);
namespace AfiuCMS\Controllers\Admin;
use AfiuCMS\Core\AuditLogger;use AfiuCMS\Core\Auth;use AfiuCMS\Core\Flash;use AfiuCMS\Core\Gate;use AfiuCMS\Core\Http\Request;use AfiuCMS\Core\Http\Response;use AfiuCMS\Core\Settings;use AfiuCMS\Core\ThemeManager;use AfiuCMS\Core\View;use Throwable;
final class ThemeController extends AdminController{
 public function __construct(View $v,Auth $a,Settings $s,private readonly ThemeManager $themes,private readonly Gate $gate,private readonly AuditLogger $audit){parent::__construct($v,$a,$s);}private function allowed():bool{return $this->gate->role()==='administrator';}
 public function index():Response{if(!$this->allowed())return $this->forbidden();return $this->page('admin.themes.index',['themes'=>$this->themes->themes()]);}
 public function activate(string $slug):Response{if(!$this->allowed())return $this->forbidden();try{$this->themes->activate($slug);$this->audit->record('theme.activated','theme',null,['slug'=>$slug]);Flash::put('success','Theme activated.');}catch(Throwable $e){Flash::put('error',$e->getMessage());}return Response::redirect('/admin/themes');}
 public function upload(Request $r):Response{if(!$this->allowed())return $this->forbidden();try{$slug=$this->themes->installZip($r->file('theme')??[]);$this->audit->record('theme.installed','theme',null,['slug'=>$slug]);Flash::put('success',"Theme {$slug} installed. Review it before activation.");}catch(Throwable $e){Flash::put('error',$e->getMessage());}return Response::redirect('/admin/themes');}
}
