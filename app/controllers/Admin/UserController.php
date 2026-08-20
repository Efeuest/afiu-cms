<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\AuditLogger; use AfiuCMS\Core\Auth; use AfiuCMS\Core\Database; use AfiuCMS\Core\Flash; use AfiuCMS\Core\Gate; use AfiuCMS\Core\Http\Request; use AfiuCMS\Core\Http\Response; use AfiuCMS\Core\Settings; use AfiuCMS\Core\View;

final class UserController extends AdminController
{
    public function __construct(View $v,Auth $a,Settings $s,private readonly Database $db,private readonly Gate $gate,private readonly AuditLogger $audit){parent::__construct($v,$a,$s);}
    private function allowed(): bool { return $this->gate->role()==='administrator'; }
    public function index(): Response { if(!$this->allowed())return $this->forbidden(); return $this->page('admin.users.index',['items'=>$this->db->all('SELECT id,name,email,role,status,last_login_at,created_at FROM users ORDER BY created_at DESC')]); }
    public function create(): Response { if(!$this->allowed())return $this->forbidden(); return $this->page('admin.users.form',['item'=>null,'error'=>null]); }
    public function store(Request $r): Response { if(!$this->allowed())return $this->forbidden(); return $this->save($r,null); }
    public function edit(string $id): Response { if(!$this->allowed())return $this->forbidden(); $item=$this->db->one('SELECT id,name,email,role,status,bio FROM users WHERE id=?',[(int)$id]); return $item?$this->page('admin.users.form',compact('item')+['error'=>null]):$this->page('errors.not-found',[],404); }
    public function update(Request $r,string $id): Response { if(!$this->allowed())return $this->forbidden(); return $this->save($r,(int)$id); }
    public function delete(string $id): Response {
        if(!$this->allowed())return $this->forbidden(); $id=(int)$id; $me=(int)($this->auth->user()['id']??0); if($id===$me){Flash::put('error','You cannot delete your own account.');return Response::redirect('/admin/users');}
        $user=$this->db->one('SELECT * FROM users WHERE id=?',[$id]); if(!$user)return Response::redirect('/admin/users');
        if($user['role']==='administrator' && (int)($this->db->one("SELECT COUNT(*) c FROM users WHERE role='administrator' AND status='active'")['c']??0)<=1){Flash::put('error','At least one active administrator is required.');return Response::redirect('/admin/users');}
        try{$this->db->execute('DELETE FROM users WHERE id=?',[$id]);$this->audit->record('user.deleted','user',$id,['email'=>$user['email']]);Flash::put('success','User deleted.');}catch(\Throwable){Flash::put('error','This user owns content or media and cannot be deleted. Disable the account instead.');} return Response::redirect('/admin/users');
    }
    private function save(Request $r,?int $id): Response {
        $name=trim((string)$r->input('name'));$email=mb_strtolower(trim((string)$r->input('email')));$role=(string)$r->input('role');$status=(string)$r->input('status');$password=(string)$r->input('password');$bio=trim((string)$r->input('bio'));
        $error=null; if($name==='')$error='Name is required.'; elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Valid email is required.'; elseif(!in_array($role,['administrator','editor','author'],true))$error='Invalid role.'; elseif(!in_array($status,['active','disabled'],true))$error='Invalid status.'; elseif($id===null && strlen($password)<10)$error='Password must contain at least 10 characters.';
        $dup=$id===null?$this->db->one('SELECT id FROM users WHERE email=?',[$email]):$this->db->one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$id]); if($dup)$error='This email is already used.';
        if($error)return $this->page('admin.users.form',['item'=>array_merge($r->all(),$id?['id'=>$id]:[]),'error'=>$error],422);
        if($id===null){$this->db->execute('INSERT INTO users (name,email,password_hash,role,status,bio) VALUES (?,?,?,?,?,?)',[mb_substr($name,0,120),$email,password_hash($password,PASSWORD_DEFAULT),$role,$status,$bio]);$id=$this->db->insertId();$action='user.created';}
        else{$current=$this->db->one('SELECT * FROM users WHERE id=?',[$id]);if(!$current)return $this->page('errors.not-found',[],404);if((int)($this->auth->user()['id']??0)===$id && $status!=='active'){$status='active';} if($current['role']==='administrator' && ($role!=='administrator'||$status!=='active')){$admins=(int)($this->db->one("SELECT COUNT(*) c FROM users WHERE role='administrator' AND status='active'")['c']??0);if($admins<=1){return $this->page('admin.users.form',['item'=>array_merge($r->all(),['id'=>$id]),'error'=>'At least one active administrator is required.'],422);}} $sql='UPDATE users SET name=?,email=?,role=?,status=?,bio=?,updated_at=CURRENT_TIMESTAMP';$params=[mb_substr($name,0,120),$email,$role,$status,$bio];if($password!==''){$sql.=',password_hash=?';$params[]=password_hash($password,PASSWORD_DEFAULT);} $sql.=' WHERE id=?';$params[]=$id;$this->db->execute($sql,$params);$action='user.updated';}
        $this->audit->record($action,'user',$id,['email'=>$email,'role'=>$role,'status'=>$status]);Flash::put('success','User saved.');return Response::redirect('/admin/users');
    }
}
