<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Category;
use App\Models\Payment;
use Illuminate\Http\Request;

class PrintReportController extends Controller
{
    public function PrintApplicationReport(Request $request)
    {
        $area_id = $request->query('area_id');
        $category_id = $request->query('category_id');
        $application_status = $request->query('application_status');
        $year = $request->query('year'); 
        // Fetch filtered applicants based on the query parameters
        $applicants = Applicant::query()
            ->when($area_id, fn ($query) => $query->where('area_id', $area_id))
            ->when($category_id, fn ($query) => $query->where('category_id', $category_id))
            ->when($application_status, fn ($query) => $query->where('status', $application_status))
            ->when($year, fn ($query) => $query->whereYear('created_at', $year))
            ->get();
        $area =  \App\Models\Area::find($area_id)?->area_name ; 
        $category = \App\Models\Category::find($category_id)?->category_name;
        return view('reports.application_report', compact('applicants', 'area', 'category', 'application_status', 'year'));      
    }



    public function PrintCategoryApplicationReport(Request $request)
    {
        $area_id = $request->query('area_id');
       
        $year = $request->query('year'); 
        // Fetch filtered applicants based on the query parameters
        $applicants = Category::query()
            ->withCount([
            'applicants as pending_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year))
                  ->where('status', 'pending');
            },

            'applicants as selected_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year))
                  ->where(function ($query) {
                        $query->where('status', 'selected')
                            ->orWhere('status', 'approved');
                    });
            },

            'applicants as approved_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year))
                  ->where('status', 'approved');
            },

            'applicants as confirmed_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year))
                  ->where('status','=', 'confirmed');
            },

            'applicants as rejected_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year))
                  ->where('status', 'rejected');
            },

            // total count
            'applicants as total_count' => function ($q) use ($area_id, $year) {
                $q->when($area_id, fn ($q) => $q->where('area_id', $area_id))
                  ->when($year, fn ($q) => $q->whereYear('applicant_year', $year));
            },
        ])
        ->selectSub(function ($query) use ($area_id, $year) {
            $query->from('payments')
                ->join('applicants', 'payments.applicant_id', '=', 'applicants.id')
                ->whereColumn('applicants.category_id', 'categories.id')
                ->when($area_id, fn ($q) => $q->where('applicants.area_id', $area_id))
                ->when($year, fn ($q) => $q->whereYear('applicants.applicant_year', $year))
                ->selectRaw('COALESCE(SUM(payments.total_paid), 0)');
        }, 'total_amount')
        ->get();
        $area =  \App\Models\Area::find($area_id)?->area_name ; 
        return view('reports.category_application_report', compact('applicants', 'area', 'year'));      
    }


    public function PrintSummeryApplicationReport(Request $request){

        $area_id = $request->query('area_id');
        $category_id = $request->query('category_id');
        $year = $request->query('year'); 
        $date = $request->query('date');
        // Fetch filtered applicants based on the query parameters
        $payments = Payment::query()
            ->whereHas('applicant', function ($query) use($area_id, $category_id,$year,$date) {
              return  $query->when($area_id, fn ($query) => $query->where('area_id', $area_id))
            ->when($category_id, fn ($query) => $query->where('category_id', $category_id))
            ->when($date, fn ($query) => $query->whereDate('updated_at', $date))
            ->when($year, fn ($query) => $query->whereYear('created_at', $year));
            })->get();
        $area =  \App\Models\Area::find($area_id)?->area_name ; 
        $category = \App\Models\Category::find($category_id)?->category_name;
        return view('reports.summery_application_report', compact('payments', 'area', 'category', 'year'));

    }

    public function PrintLotteryToken(Request $request){
        $area_id = $request->query('area_id');
        $category_id = $request->query('category_id');
        $status=$request->query('status');
        $year = $request->query('year'); 
        $applicants = Applicant::query()
            ->when($area_id, fn ($query) => $query->where('area_id', $area_id))
            ->when($category_id, fn ($query) => $query->where('category_id', $category_id))
            ->when($year, fn ($query) => $query->whereYear('applicant_year', $year))
            ->where('status', $status)
            ->get();
        $area = \App\Models\Area::find($area_id)?->area_name;
        $category = \App\Models\Category::find($category_id)?->category_name;
        return view('receipt.tokens_print', compact('applicants', 'area', 'category','status', 'year'));
    }

    
}
