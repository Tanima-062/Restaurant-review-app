<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettlementCompanyRequest;
use App\Http\Requests\Admin\SettlementCompanySearchRequest;
use App\Models\SettlementCompany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class SettlementCompanyController extends Controller
{
    protected $redirectTo = 'admin/settlement_company';

    public function index(SettlementCompanySearchRequest $request)
    {
        $const = config('const.settlement');
        $data = SettlementCompany::adminSearchFilter($request->validated())->sortable()->paginate(30);

        return view('admin.SettlementCompany.index',
            [
                'page' => $request->get('page', 1),
                'settlementCompanies' => $data,
                'cycle' => Arr::pluck($const['payment_cycle'], 'short', 'value'),
                'baseAmount' => Arr::pluck($const['result_base_amount'], 'label', 'value'),
                'tax' => Arr::pluck($const['tax_calculation'], 'label', 'value'),
            ]
        );
    }

    public function editForm(int $id)
    {
        $const = config('const.settlement');

        $data = SettlementCompany::find($id);

        return view('admin.SettlementCompany.edit',
            [
                'settlementCompany' => $data,
                'cycle' => $const['payment_cycle'],
                'baseAmount' => $const['result_base_amount'],
                'taxCalculation' => $const['tax_calculation'],
                'accountType' => $const['account_type'],
            ]
        );
    }

    public function edit(SettlementCompanyRequest $request, int $id)
    {
        try {
            \DB::beginTransaction();
            $data = $request->except(['_token', 'redirect_to']);
            $data['published'] = (!empty($data['published'])) ? 1 : 0;

            $settlementCompany = SettlementCompany::find($id);
            $settlementCompany->setRawAttributes($data);
            $settlementCompany->save();
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($this->redirectTo)
            ->with('custom_error', sprintf('精算会社「%s」を更新できませんでした', $request->name));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf('精算会社「%s」を更新しました', $data['name']));
    }

    public function addForm()
    {
        $const = config('const.settlement');

        return view('admin.SettlementCompany.add',
            [
                'cycle' => $const['payment_cycle'],
                'baseAmount' => $const['result_base_amount'],
                'taxCalculation' => $const['tax_calculation'],
                'accountType' => $const['account_type'],
            ]
        );
    }

    public function add(SettlementCompanyRequest $request)
    {
        try {
            \DB::beginTransaction();
            $data = $request->except(['_token', 'redirect_to']);
            $data['published'] = (!empty($data['published'])) ? 1 : 0;
            $data['staff_id'] = (!empty((Auth::user())->id)) ? (Auth::user())->id : null;
            SettlementCompany::create($data);
            \DB::commit();
        } catch (\Throwable $e) {
            report($e);
            \DB::rollBack();

            return redirect($this->redirectTo)
            ->with('custom_error', sprintf('精算会社「%s」を作成できませんでした', $request->name));
        }

        return redirect($this->redirectTo)
            ->with('message', sprintf('精算会社「%s」を作成しました', $data['name']));
    }
}
