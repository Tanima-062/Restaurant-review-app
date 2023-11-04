<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SettlementConfirmRequest;
use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use App\Modules\Settlement\ClientInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Auth;

class SettlementConfirmController
{
    public function index(SettlementConfirmRequest $request)
    {
        $settlementCompanyName = $request->input('settlementCompanyName', '');
        $monthSt = $request->input('monthSt', '');
        $monthEd = $request->input('monthEd', '');

        if ((Auth::user())->staff_authority_id == config('const.staff.authority.SETTLEMENT_ADMINISTRATOR')) {
            $settlementCompanies = SettlementCompany::where('id', Auth::user()->settlement_company_id)
                ->where('published', 1)->get();
            $settlementDownloadFull = SettlementDownload::whereHas('settlementCompany', function ($query) use ($settlementCompanies) {
                $query->where('settlement_company_id', $settlementCompanies->pluck('id')->all());
            })->get();
        } else {
            $settlementCompanies = SettlementCompany::where('published', 1)->get();
            $settlementDownloadFull = SettlementDownload::with('settlementCompany')->get();
        }

        $settlementDownloads = $settlementDownloadFull->whereBetween('month', [$monthSt, $monthEd]);
        if (!empty($settlementCompanyName)) {
            $tmp = $settlementCompanies->filter(function ($settlementCompany) use ($settlementCompanyName) {
                return strpos($settlementCompany->name, $settlementCompanyName) !== false;
            });

            $settlementDownloads = $settlementDownloads->whereIn('settlement_company_id', $tmp->pluck('id')->all());
        }
        $settlementDownloads = $settlementDownloads->sortBy('month');
        $settlementYYYYmm = $settlementDownloadFull->unique('month')->sortBy('month')->pluck('month')->all();

        $settlementType = collect(config('const.settlement.settlement_type'))->keyBy('value')->all();

        return view('admin.SettlementConfirm.index', [
            'settlementCompanies' => $settlementCompanies,
            'settlementDownloads' => $settlementDownloads,
            'settlementYYYYmm' => $settlementYYYYmm,
            'settlementType' => $settlementType
        ]);
    }

    public function pdfDownload(Request $request)
    {
        $settlementCompanyId = $request->input('settlement_company_id');
        $month = $request->input('month');

        if (!Auth::user()->can('inHouseGeneral-higher')) {
            if ((Auth::user())->settlement_company_id !== (int)$settlementCompanyId) {
                abort(403);
            }
        }

        $settlementDownload = SettlementDownload::where('settlement_company_id', $settlementCompanyId)
            ->where('month', $month)->first();
        if (is_null($settlementDownload)) {
            abort(404);
        }

        $settlementDownload->download_at = Carbon::now();
        $settlementDownload->save();

        $pdf = \App::make('snappy.pdf.wrapper');
        // bladeのテンプレートを予約データ入りで読み込む
        $clientInvoice = new ClientInvoice($settlementCompanyId, $month);
        $clientInvoice->agg();

        return $pdf->loadView('pdf.settlement', ['clientInvoice' => $clientInvoice])
            ->download($settlementCompanyId.'_'.$month.'_invoice.pdf');
        //return $pdf->loadView('pdf.settlement', ['clientInvoice' => $clientInvoice])->inline();
        //return view('pdf.settlement', ['clientInvoice' => $clientInvoice]);
    }
}
