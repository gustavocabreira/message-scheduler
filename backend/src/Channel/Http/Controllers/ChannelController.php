<?php

declare(strict_types=1);

namespace Src\Channel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Channel\Actions\ListChannelEntrypointsAction;
use Src\Channel\Actions\ListChannelsAction;
use Src\Channel\Http\Requests\ListChannelsRequest;
use Src\Channel\Http\Resources\ChannelEntrypointResource;
use Src\Channel\Http\Resources\ChannelResource;
use Src\Channel\Models\Channel;
use Src\Tenant\Actions\GetActiveTenantAction;
use Src\Tenant\Models\Tenant;

#[Group(name: 'Channels')]
final class ChannelController extends Controller
{
    #[Endpoint(
        operationId: 'channels.index',
        title: 'List channels',
        description: 'Retorna os canais disponíveis na plataforma, ordenados alfabeticamente. Por padrão lista apenas os canais ativos. Aceita filtro opcional por nome (busca parcial, case-insensitive) e por status (active, inactive, all).',
    )]
    public function index(ListChannelsRequest $request, ListChannelsAction $action): AnonymousResourceCollection
    {
        $name = $request->string('name')->isNotEmpty() ? (string) $request->string('name') : null;
        $status = $request->string('status', 'active')->toString();

        return ChannelResource::collection($action->handle($name, $status));
    }

    #[Endpoint(
        operationId: 'channels.entrypoints',
        title: 'List channel entrypoints',
        description: 'Retorna os entrypoints de um canal a partir da API Huggy. Resultados são cacheados por 5 minutos. Requer workspace ativo na sessão.',
    )]
    #[Response(status: 404, description: 'Canal ou workspace ativo não encontrado.')]
    public function entrypoints(
        Request $request,
        GetActiveTenantAction $getTenantAction,
        ListChannelEntrypointsAction $action,
        string $channel,
    ): AnonymousResourceCollection {
        /** @var User $user */
        $user = $request->user();

        $tenant = $getTenantAction->handle($user, $request->session()->get('active_tenant_id'));

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        /** @var Channel|null $channelModel */
        $channelModel = Channel::query()->where('slug', $channel)->first();

        if (! $channelModel instanceof Channel) {
            abort(404);
        }

        $entrypoints = $action->handle($user, $tenant, $channelModel);

        return ChannelEntrypointResource::collection($entrypoints);
    }
}
