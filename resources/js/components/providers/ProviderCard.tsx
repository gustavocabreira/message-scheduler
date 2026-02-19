import { useState } from 'react';
import { Loader2, RefreshCw, Trash2, CheckCircle2, XCircle, Clock } from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useTestConnection, useDeleteProvider } from '@/hooks/useProviders';
import type { ProviderConnection, ProviderStatus } from '@/types/provider';

interface ProviderCardProps {
    provider: ProviderConnection;
}

function StatusBadge({ status }: { status: ProviderStatus }) {
    if (status === 'active') {
        return (
            <Badge variant="outline" className="border-green-500 text-green-600 dark:text-green-400">
                <CheckCircle2 className="mr-1 size-3" />
                Ativo
            </Badge>
        );
    }
    if (status === 'error') {
        return (
            <Badge variant="outline" className="border-destructive text-destructive">
                <XCircle className="mr-1 size-3" />
                Erro
            </Badge>
        );
    }
    return (
        <Badge variant="outline" className="text-muted-foreground">
            <Clock className="mr-1 size-3" />
            Inativo
        </Badge>
    );
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(iso));
}

export function ProviderCard({ provider }: ProviderCardProps) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const testMutation = useTestConnection();
    const deleteMutation = useDeleteProvider();

    function handleTestConnection() {
        testMutation.mutate(provider.id, {
            onSuccess: (data) => {
                if (data.connected) {
                    toast.success('Conexão com a Huggy está funcionando corretamente.');
                } else {
                    toast.warning('Conexão com a Huggy falhou. Verifique suas credenciais.');
                }
            },
            onError: () => {
                toast.error('Erro ao testar a conexão. Tente novamente.');
            },
        });
    }

    function handleDelete() {
        deleteMutation.mutate(provider.id, {
            onSuccess: () => {
                toast.success(`Conexão com ${provider.provider_label} removida com sucesso.`);
                setDeleteOpen(false);
            },
            onError: () => {
                toast.error('Erro ao remover a conexão. Tente novamente.');
            },
        });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
                <div className="flex items-center gap-3">
                    <div className="size-10 rounded-lg bg-muted flex items-center justify-center">
                        <HuggyLogo className="size-6" />
                    </div>
                    <div>
                        <CardTitle className="text-base">{provider.provider_label}</CardTitle>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            {provider.provider_type}
                        </p>
                    </div>
                </div>
                <StatusBadge status={provider.status} />
            </CardHeader>

            <CardContent className="pb-3">
                <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt className="text-muted-foreground">Conectado em</dt>
                    <dd>{formatDate(provider.connected_at)}</dd>
                    <dt className="text-muted-foreground">Última sync</dt>
                    <dd>{formatDate(provider.last_synced_at)}</dd>
                </dl>
            </CardContent>

            <CardFooter className="flex gap-2 pt-0">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleTestConnection}
                    disabled={testMutation.isPending}
                    className="flex-1"
                >
                    {testMutation.isPending ? (
                        <Loader2 className="mr-2 size-4 animate-spin" />
                    ) : (
                        <RefreshCw className="mr-2 size-4" />
                    )}
                    Testar conexão
                </Button>

                <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                    <AlertDialogTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            aria-label="Remover conexão"
                            className="text-destructive hover:text-destructive"
                            disabled={deleteMutation.isPending}
                        >
                            {deleteMutation.isPending ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Trash2 className="size-4" />
                            )}
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Remover conexão</AlertDialogTitle>
                            <AlertDialogDescription>
                                Tem certeza que deseja remover a conexão com{' '}
                                <strong>{provider.provider_label}</strong>? Esta ação não pode ser
                                desfeita e todos os agendamentos vinculados a este provider poderão
                                ser afetados.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={handleDelete}
                                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            >
                                Remover
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </CardFooter>
        </Card>
    );
}

function HuggyLogo({ className }: { className?: string }) {
    return (
        <svg
            className={className}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <rect width="32" height="32" rx="8" fill="#5C6BC0" />
            <text
                x="16"
                y="22"
                textAnchor="middle"
                fontSize="14"
                fontWeight="bold"
                fill="white"
                fontFamily="sans-serif"
            >
                H
            </text>
        </svg>
    );
}
