<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class PdfExportService
{
    /**
     * Generate a PDF-ready HTML response from a Blade view.
     *
     * @param  string  $view     Blade view name
     * @param  array   $data     Data to pass to the view
     * @param  string  $filename Download filename (without extension)
     * @return Response
     */
    public function generateReport(string $view, array $data, string $filename): Response
    {
        $html = View::make($view, $data)->render();

        $styledHtml = $this->wrapWithPrintStyles($html);

        return response($styledHtml)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.html"');
    }

    /**
     * Wrap the rendered HTML with print-friendly styles.
     */
    private function wrapWithPrintStyles(string $html): string
    {
        $printStyles = <<<'CSS'
<style>
    @page { size: A4; margin: 15mm; }
    @media print {
        body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print { display: none !important; }
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 20px;
        font-family: 'Helvetica Neue', Arial, sans-serif;
        font-size: 13px;
        color: #1e1b4b;
        line-height: 1.5;
        background: #ffffff;
    }
    table { width: 100%; border-collapse: collapse; margin: 16px 0; }
    th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background-color: #f3f4f6; font-weight: 600; color: #374151; }
    h1, h2, h3 { margin: 0 0 12px; color: #1e1b4b; }
    .report-header { border-bottom: 3px solid #7c3aed; padding-bottom: 16px; margin-bottom: 24px; }
    .report-footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 11px; text-align: center; }
    .metric-box { display: inline-block; width: 23%; margin: 0 1% 16px 0; padding: 16px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; }
    .metric-label { font-size: 11px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    .metric-value { font-size: 20px; font-weight: 700; color: #7c3aed; }
</style>
CSS;

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Report</title>' . $printStyles . '</head><body>' . $html . '</body></html>';
    }
}