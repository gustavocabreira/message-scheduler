import { Plug } from 'lucide-react';
import { ProviderCard } from '@/components/providers/ProviderCard';
import { ConnectHuggyButton } from '@/components/providers/ConnectHuggyButton';
import { useProviders } from '@/hooks/useProviders';
import { Skeleton } from '@/components/ui/skeleton';

function ProviderCardSkeleton() {
    return (
        <div className="rounded-lg border p-5 space-y-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Skeleton className="size-10 rounded-lg" />
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-20" />
                        <Skeleton className="h-3 w-12" />
                    </div>
                </div>
                <Skeleton className="h-5 w-16 rounded-full" />
            </div>
            <div className="grid grid-cols-2 gap-2">
                <Skeleton className="h-3 w-24" />
                <Skeleton className="h-3 w-20" />
                <Skeleton className="h-3 w-24" />
                <Skeleton className="h-3 w-20" />
            </div>
            <div className="flex gap-2">
                <Skeleton className="h-8 flex-1" />
                <Skeleton className="h-8 w-9" />
            </div>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <div className="size-16 rounded-full bg-muted flex items-center justify-center mb-4">
                <Plug className="size-8 text-muted-foreground" />
            </div>
            <h2 className="text-lg font-semibold mb-1">Nenhum provider conectado</h2>
            <p className="text-sm text-muted-foreground max-w-xs mb-6">
                Conecte sua conta Huggy para começar a agendar mensagens para seus contatos.
            </p>
            <ConnectHuggyButton />
        </div>
    );
}

export function ProvidersPage() {
    const { data, isLoading, isError } = useProviders();
    const providers = data?.data ?? [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Providers</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Gerencie suas integrações com plataformas de mensagens.
                    </p>
                </div>
                {!isLoading && providers.length > 0 && <ConnectHuggyButton variant="outline" size="sm" />}
            </div>

            {isError && (
                <div className="rounded-lg border border-destructive/50 bg-destructive/5 p-4 text-sm text-destructive">
                    Erro ao carregar os providers. Tente recarregar a página.
                </div>
            )}

            {isLoading && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <ProviderCardSkeleton />
                    <ProviderCardSkeleton />
                </div>
            )}

            {!isLoading && !isError && providers.length === 0 && <EmptyState />}

            {!isLoading && !isError && providers.length > 0 && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {providers.map((provider) => (
                        <ProviderCard key={provider.id} provider={provider} />
                    ))}
                </div>
            )}
        </div>
    );
}
