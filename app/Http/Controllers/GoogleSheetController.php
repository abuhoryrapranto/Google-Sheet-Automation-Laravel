<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheet;

class GoogleSheetController extends Controller
{
    public $googleSheetService;

    public function __construct(GoogleSheet $googleSheetService) 
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function fetchDataFromGoogleSheet() {
        $sheetId = config('sheet.google_sheet_id');
        $data = $this->googleSheetService->readGoogleSheet($sheetId);
        return $data;
    }
}
