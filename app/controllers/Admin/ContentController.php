<?php

declare(strict_types=1);

namespace AfiuCMS\Controllers\Admin;

use AfiuCMS\Core\AuditLogger;
use AfiuCMS\Core\Auth;
use AfiuCMS\Core\Database;
use AfiuCMS\Core\Flash;
use AfiuCMS\Core\Gate;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Settings;
use AfiuCMS\Core\Slugger;
use AfiuCMS\Core\View;

final class ContentController extends AdminController
{
    public function __construct(View $view, Auth $auth, Settings $settings, private readonly Database $db, private readonly Gate $gate, private readonly AuditLogger $audit) { parent::__construct($view,$auth,$settings); }
    public function pages(Request $r): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->listing($r,'page'); }
    public function posts(Request $r): Response { return $this->listing($r,'post'); }
    public function createPage(): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->form('page'); }
    public function createPost(): Response { return $this->form('post'); }
    public function storePage(Request $r): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->store($r,'page'); }
    public function storePost(Request $r): Response { return $this->store($r,'post'); }
    public function editPage(string $id): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->edit((int)$id,'page'); }
    public function editPost(string $id): Response { return $this->edit((int)$id,'post'); }
    public function updatePage(Request $r,string $id): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->update($r,(int)$id,'page'); }
    public function updatePost(Request $r,string $id): Response { return $this->update($r,(int)$id,'post'); }
    public function deletePage(string $id): Response { if (!$this->gate->allows('content.write')) return $this->forbidden(); return $this->delete((int)$id,'page'); }
    public function deletePost(string $id): Response { return $this->delete((int)$id,'post'); }

    private function listing(Request $request,string $type): Response
    {
        $q=trim((string)$request->query('q','')); $status=(string)$request->query('status',''); $page=max(1,(int)$request->query('page',1)); $per=20; $where=['c.type=?']; $params=[$type];
        if ($this->gate->role()==='author') { $where[]='c.author_id=?'; $params[]=(int)($this->auth->user()['id']??0); }
        if (in_array($status,['draft','published'],true)) { $where[]='c.status=?'; $params[]=$status; }
        if ($q!=='') { $where[]='(c.title LIKE ? OR c.slug LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
        $sqlWhere=implode(' AND ',$where); $total=(int)($this->db->one("SELECT COUNT(*) c FROM content c WHERE {$sqlWhere}",$params)['c']??0); $offset=($page-1)*$per;
        $items=$this->db->all("SELECT c.*,u.name author_name FROM content c JOIN users u ON u.id=c.author_id WHERE {$sqlWhere} ORDER BY c.updated_at DESC LIMIT {$per} OFFSET {$offset}",$params);
        return $this->page('admin.content.index',['items'=>$items,'type'=>$type,'q'=>$q,'status'=>$status,'pagination'=>['page'=>$page,'pages'=>max(1,(int)ceil($total/$per)),'total'=>$total]]);
    }

    private function form(string $type,?array $item=null,?string $error=null): Response
    {
        if ($type==='page' && !$this->gate->allows('content.write')) return $this->forbidden();
        if ($type==='post' && !$this->gate->allows('content.write') && !$this->gate->allows('post.write')) return $this->forbidden();
        if ($item && $this->gate->role()==='author' && (int)$item['author_id'] !== (int)($this->auth->user()['id']??0)) return $this->forbidden();
        $categories=$this->db->all("SELECT * FROM taxonomies WHERE type='category' ORDER BY name");
        $selected=[]; $tags='';
        if ($item) {
            $selected=array_map('intval',array_column($this->db->all("SELECT t.id FROM taxonomies t JOIN content_taxonomy ct ON ct.taxonomy_id=t.id WHERE ct.content_id=? AND t.type='category'",[(int)$item['id']]),'id'));
            $tags=implode(', ',array_column($this->db->all("SELECT t.name FROM taxonomies t JOIN content_taxonomy ct ON ct.taxonomy_id=t.id WHERE ct.content_id=? AND t.type='tag' ORDER BY t.name",[(int)$item['id']]),'name'));
        }
        $media=$this->db->all('SELECT id,original_name,alt_text FROM media ORDER BY created_at DESC LIMIT 200');
        $revisions=$item ? $this->db->all('SELECT r.*,u.name user_name FROM content_revisions r JOIN users u ON u.id=r.user_id WHERE r.content_id=? ORDER BY r.created_at DESC LIMIT 10',[(int)$item['id']]) : [];
        return $this->page('admin.content.form',compact('type','item','error','categories','selected','tags','media','revisions'));
    }

    private function store(Request $r,string $type): Response
    {
        if ($type==='post' && !$this->gate->allows('content.write') && !$this->gate->allows('post.write')) return $this->forbidden();
        [$d,$error]=$this->validate($r,null); if($error) return $this->form($type,$r->all(),$error);
        $this->db->execute('INSERT INTO content (type,title,slug,excerpt,body,status,seo_title,seo_description,canonical_url,featured_media_id,author_id,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',[$type,$d['title'],$d['slug'],$d['excerpt'],$d['body'],$d['status'],$d['seo_title'],$d['seo_description'],$d['canonical_url'],$d['featured_media_id'],(int)($this->auth->user()['id']??0),$d['published_at']]);
        $id=$this->db->insertId(); $this->syncTaxonomies($id,$r); $this->audit->record('content.created',$type,$id,['title'=>$d['title'],'status'=>$d['status']]); Flash::put('success',ucfirst($type).' created.'); return Response::redirect('/admin/'.($type==='page'?'pages':'posts'));
    }

    private function edit(int $id,string $type): Response
    {
        $item=$this->db->one('SELECT * FROM content WHERE id=? AND type=?',[$id,$type]); return $item ? $this->form($type,$item) : $this->page('errors.not-found',[],404);
    }

    private function update(Request $r,int $id,string $type): Response
    {
        $old=$this->db->one('SELECT * FROM content WHERE id=? AND type=?',[$id,$type]); if(!$old) return $this->page('errors.not-found',[],404);
        if ($this->gate->role()==='author' && (int)$old['author_id'] !== (int)($this->auth->user()['id']??0)) return $this->forbidden();
        [$d,$error]=$this->validate($r,$id); if($error) return $this->form($type,array_merge($old,$r->all()),$error);
        if ($d['status']==='published' && !empty($old['published_at'])) $d['published_at']=$old['published_at'];
        $this->db->execute('INSERT INTO content_revisions (content_id,user_id,title,excerpt,body) VALUES (?,?,?,?,?)',[$id,(int)($this->auth->user()['id']??0),$old['title'],$old['excerpt'],$old['body']]);
        $this->db->execute('UPDATE content SET title=?,slug=?,excerpt=?,body=?,status=?,seo_title=?,seo_description=?,canonical_url=?,featured_media_id=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND type=?',[$d['title'],$d['slug'],$d['excerpt'],$d['body'],$d['status'],$d['seo_title'],$d['seo_description'],$d['canonical_url'],$d['featured_media_id'],$d['published_at'],$id,$type]);
        $this->syncTaxonomies($id,$r); $this->audit->record('content.updated',$type,$id,['title'=>$d['title'],'status'=>$d['status']]); Flash::put('success',ucfirst($type).' updated.'); return Response::redirect('/admin/'.($type==='page'?'pages':'posts'));
    }

    private function delete(int $id,string $type): Response
    {
        $item=$this->db->one('SELECT * FROM content WHERE id=? AND type=?',[$id,$type]); if(!$item) return Response::redirect('/admin/'.($type==='page'?'pages':'posts'));
        if ($this->gate->role()==='author' && (int)$item['author_id'] !== (int)($this->auth->user()['id']??0)) return $this->forbidden();
        $this->db->execute('DELETE FROM content WHERE id=? AND type=?',[$id,$type]); $this->audit->record('content.deleted',$type,$id,['title'=>$item['title']]); Flash::put('success',ucfirst($type).' deleted.'); return Response::redirect('/admin/'.($type==='page'?'pages':'posts'));
    }

    private function validate(Request $r,?int $ignoreId): array
    {
        $title=trim((string)$r->input('title')); $slug=Slugger::make(trim((string)$r->input('slug',''))?:$title); $status=(string)$r->input('status','draft');
        if($title==='') return [[], 'Title is required.']; if(!in_array($status,['draft','published'],true)) return [[], 'Invalid publication status.'];
        $dup=$ignoreId===null?$this->db->one('SELECT id FROM content WHERE slug=? LIMIT 1',[$slug]):$this->db->one('SELECT id FROM content WHERE slug=? AND id<>? LIMIT 1',[$slug,$ignoreId]); if($dup) return [[], 'This slug is already used.'];
        $featured=(int)$r->input('featured_media_id',0);
        return [[
            'title'=>mb_substr($title,0,255),'slug'=>$slug,'excerpt'=>trim((string)$r->input('excerpt')),'body'=>(string)$r->input('body'),'status'=>$status,
            'seo_title'=>mb_substr(trim((string)$r->input('seo_title')),0,255)?:null,'seo_description'=>mb_substr(trim((string)$r->input('seo_description')),0,320)?:null,
            'canonical_url'=>mb_substr(trim((string)$r->input('canonical_url')),0,500)?:null,'featured_media_id'=>$featured>0?$featured:null,
            'published_at'=>$status==='published' ? ((string)$r->input('published_at') ?: date('Y-m-d H:i:s')) : null,
        ],null];
    }

    private function syncTaxonomies(int $contentId,Request $r): void
    {
        $this->db->execute('DELETE FROM content_taxonomy WHERE content_id=?',[$contentId]);
        $categoryIds=$r->input('categories',[]); if(!is_array($categoryIds)) $categoryIds=[];
        foreach($categoryIds as $id){ $id=(int)$id; if($id>0) $this->db->execute('INSERT IGNORE INTO content_taxonomy (content_id,taxonomy_id) VALUES (?,?)',[$contentId,$id]); }
        $names=array_unique(array_filter(array_map('trim',explode(',',(string)$r->input('tags','')))));
        foreach(array_slice($names,0,30) as $name){ $slug=Slugger::make($name); if($slug==='') continue; $tag=$this->db->one("SELECT id FROM taxonomies WHERE type='tag' AND slug=?",[$slug]); if(!$tag){$this->db->execute("INSERT INTO taxonomies (type,name,slug) VALUES ('tag',?,?)",[mb_substr($name,0,120),$slug]); $tag=['id'=>$this->db->insertId()];} $this->db->execute('INSERT IGNORE INTO content_taxonomy (content_id,taxonomy_id) VALUES (?,?)',[$contentId,(int)$tag['id']]); }
    }
}
