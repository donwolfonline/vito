<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function search(Request $request): JsonResponse
    {
        $this->validate($request, [
            'q' => 'required',
        ]);

        $servers = Server::query()
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->input('q').'%')
                    ->orWhere('ip', 'like', '%'.$request->input('q').'%');
            })
            ->get();

        $sites = Site::query()
            ->where('domain', 'like', '%'.$request->input('q').'%')
            ->get();

        $result = [];

        /** @var Server $server */
        foreach ($servers as $server) {
            $result[] = [
                'type' => 'server',
                'url' => route('servers.show', ['server' => $server]),
                'text' => $server->name,
                'project' => $server->project->name,
            ];
        }

        /** @var Site $site */
        foreach ($sites as $site) {
            $result[] = [
                'type' => 'site',
                'url' => route('servers.sites.show', ['server' => $site->server, 'site' => $site]),
                'text' => $site->domain,
                'project' => $site->server->project->name,
            ];
        }

        return response()->json([
            'results' => $result,
        ]);
    }
}
