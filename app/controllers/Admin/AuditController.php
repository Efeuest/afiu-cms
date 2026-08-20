<?php

declare(strict_types=1);
namespace AfiuCMS\Controllers\Admin;
use AfiuCMS\Core\Auth;use AfiuCMS\Core\Database;use AfiuCMS\Core\Gate;use AfiuCMS\Core\Http\Request;use AfiuCMS\Core\Http\Response;use AfiuCMS\Core\Settings;use AfiuCMS\Core\View;
final class AuditController extends AdminController{
 public function __construct(View $v,Auth $a,Settings $s,private readonly Database $db,private readonly Gate $gate){parent::__construct($v,$a,$s);} public function index(Request $r):Response{if($this->gate->role()!=='administrator')return $this->forbidden();$q=trim((string)$r->query('q',''));$params=[];$where='1=1';if($q!==''){$where='(a.action LIKE ? OR a.entity_type LIKE ? OR u.name LIKE ?)';$params=array_fill(0,3,'%'.$q.'%');}$items=$this->db->all("SELECT a.*,u.name user_name,u.email user_email FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE {$where} ORDER BY a.created_at DESC LIMIT 200",$params);return $this->page('admin.audit.index',compact('items','q'));}}
