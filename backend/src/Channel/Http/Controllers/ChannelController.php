<?php

declare(strict_types=1);

namespace Src\Channel\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Channel\Actions\ListChannelsAction;
use Src\Channel\Http\Requests\ListChannelsRequest;
use Src\Channel\Http\Resources\ChannelResource;

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
}
