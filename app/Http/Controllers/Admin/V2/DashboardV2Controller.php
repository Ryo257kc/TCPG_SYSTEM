<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\DashboardV2MenuService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardV2Controller extends Controller
{
    public function __construct(private readonly DashboardV2MenuService $menuService)
    {
    }

    public function index(Request $request): View
    {
        $requestedPage = (string) $request->query('page', 'home');
        return $this->renderPage($requestedPage);
    }

    public function bonus(): View
    {
        return $this->renderPage('bonus-detail');
    }

    public function masterCompany(): View
    {
        return $this->renderPage('master-company');
    }

    public function masterStaff(): View
    {
        return $this->renderPage('master-staff');
    }

    public function masterStore(): View
    {
        return $this->renderPage('master-store');
    }

    public function masterAllowance(): View
    {
        return $this->renderPage('master-allowance');
    }

    private function renderPage(string $pageKey): View
    {
        $menuGroups = $this->menuService->menuGroups();
        $pages = $this->menuService->pages();

        $selectedPage = array_key_exists($pageKey, $pages) ? $pageKey : 'home';

        return view('admin_v2.dashboard.index', [
            'menuGroups' => $menuGroups,
            'selectedPage' => $selectedPage,
            'pageData' => $pages[$selectedPage],
        ]);
    }
}