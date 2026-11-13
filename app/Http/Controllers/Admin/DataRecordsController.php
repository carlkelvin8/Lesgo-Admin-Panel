<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDataCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DataRecordsController extends Controller
{
    public function __construct(private readonly AdminDataCatalog $catalog) {}

    public function hub(Request $request)
    {
        return view('admin.data.hub', ['datasets' => collect($this->catalog->availableTo($request->user()))->groupBy('group', true)]);
    }

    public function index(Request $request, string $dataset)
    {
        $definition = $this->catalog->definition($dataset, $request->user());
        if (! $definition['available']) {
            return response()->view('admin.data.unavailable', compact('definition'), 503);
        }
        $records = $this->catalog->query($definition, $request)->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.data.index', ['definition' => $definition, 'records' => $records, 'catalog' => $this->catalog]);
    }

    public function show(Request $request, string $dataset, string $id)
    {
        $definition = $this->catalog->definition($dataset, $request->user());
        abort_unless($definition['available'], 503, 'These records are not available in this environment.');
        $record = $this->catalog->query($definition, $request)->where('id', $id)->first();
        abort_unless($record, 404);

        return view('admin.data.show', ['definition' => $definition, 'record' => $record, 'catalog' => $this->catalog]);
    }

    public function export(Request $request, string $dataset)
    {
        $definition = $this->catalog->definition($dataset, $request->user());
        abort_unless($definition['available'], 503, 'These records are not available in this environment.');
        $query = $this->catalog->query($definition, $request);

        return response()->streamDownload(function () use ($query, $definition) {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_map(fn ($field) => Str::headline($field), $definition['fields']), escape: '');
            $query->orderBy('id')->chunkById(500, function ($records) use ($output, $definition) {
                foreach ($records as $record) {
                    $row = array_map(function ($field) use ($definition, $record) {
                        $value = $this->catalog->display($definition, $field, $record->$field);

                        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
                    }, $definition['fields']);
                    fputcsv($output, $row, escape: '');
                }
            });
            fclose($output);
        }, $dataset.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
