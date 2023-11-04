<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoticeRequest;
use App\Http\Requests\Admin\NoticeSearchRequest;
use App\Models\Notice;
use App\Models\Staff;

class NoticeController extends Controller
{
    public function index(NoticeSearchRequest $request)
    {
        $staffs = Staff::admin()->published()->orderBy('id')->get();
        $notice = Notice::with('createdBy', 'updatedBy')->adminSearchFilter($request->validated())
            ->orderBy('id', 'desc')->paginate(30);

        return view('admin.Notice.index', ['notices' => $notice, 'staffs' => $staffs]);
    }

    public function editForm(int $id)
    {
        return view('admin.Notice.edit', ['notice' => Notice::find($id)]);
    }

    public function edit(NoticeRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $notice = Notice::find($id);
            $notice->app_cd = $request->input('app_cd');
            $notice->title = $request->input('title');
            $notice->message = $request->input('message');
            $notice->published_at = $request->input('published_at');
            $notice->datetime_from = $request->input('datetime_from');
            $notice->datetime_to = $request->input('datetime_to');
            $notice->ui_website_flg = (!empty($request->input('ui_website_flg'))) ? 1 : 0;
            $notice->ui_admin_flg = (!empty($request->input('ui_admin_flg'))) ? 1 : 0;
            $notice->published = (!empty($request->input('published'))) ? 1 : 0;
            $notice->save();
            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();

            return redirect($request->input('redirect_to'))->with('custom_error', sprintf('お知らせ「%s」を更新できませんでした。', $request->title));
        }

        return redirect($request->input('redirect_to'))->with('message', sprintf('お知らせ「%s」を更新しました。', $notice->title));
    }

    public function addForm()
    {
        return view('admin.Notice.add');
    }

    public function add(NoticeRequest $request)
    {
        
        try {
            \DB::beginTransaction();
            $valid = $request->validated();
            Notice::firstOrCreate([
            'app_cd' => $request->input('app_cd'),
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'published_at' => $request->input('published_at'),
            'datetime_from' => $request->input('datetime_from'),
            'datetime_to' => $request->input('datetime_to'),
            'ui_website_flg' => (!empty($request->input('ui_website_flg'))) ? 1 : 0,
            'ui_admin_flg' => (!empty($request->input('ui_admin_flg'))) ? 1 : 0,
            'published' => (!empty($request->input('published'))) ? 1 : 0,
        ]);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($request->input('redirect_to'))->with('custom_error', sprintf('お知らせ「%s」を作成できませんでした', $request->title));
        }

        return redirect($request->input('redirect_to'))->with('message', sprintf("お知らせ「%s」を作成しました", $valid['title']));
    }
}
