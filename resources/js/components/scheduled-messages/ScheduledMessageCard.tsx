import { useState } from 'react';
import {
    Clock,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Ban,
    Loader2,
    ScrollText,
    Trash2,
    RefreshCw,
} from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
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
import { MessageLogsDialog } from './MessageLogsDialog';
import { useCancelScheduledMessage } from '@/hooks/useScheduledMessages';
import type { ScheduledMessage, ScheduledMessageStatus } from '@/types/scheduled-message';

interface ScheduledMessageCardProps {
    message: ScheduledMessage;
}

function StatusBadge({ status }: { status: ScheduledMessageStatus }) {
    switch (status) {
        case 'pending':
            return (
                <Badge variant="outline" className="text-muted-foreground">
                    <Clock className="mr-1 size-3" />
                    Pendente
                </Badge>
            );
        case 'processing':
            return (
                <Badge variant="outline" className="border-yellow-500 text-yellow-600 dark:text-yellow-400">
                    <RefreshCw className="mr-1 size-3 animate-spin" />
                    Processando
                </Badge>
            );
        case 'sent':
            return (
                <Badge variant="outline" className="border-green-500 text-green-600 dark:text-green-400">
                    <CheckCircle2 className="mr-1 size-3" />
                    Enviado
                </Badge>
            );
        case 'failed':
            return (
                <Badge variant="outline" className="border-destructive text-destructive">
                    <XCircle className="mr-1 size-3" />
                    Falhou
                </Badge>
            );
        case 'cancelled':
            return (
                <Badge variant="outline" className="text-muted-foreground">
                    <Ban className="mr-1 size-3" />
                    Cancelado
                </Badge>
            );
        default:
            return (
                <Badge variant="outline">
                    <AlertCircle className="mr-1 size-3" />
                    {status}
                </Badge>
            );
    }
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(iso));
}

export function ScheduledMessageCard({ message }: ScheduledMessageCardProps) {
    const [cancelOpen, setCancelOpen] = useState(false);
    const [logsOpen, setLogsOpen] = useState(false);
    const cancelMutation = useCancelScheduledMessage();

    const canCancel = message.status === 'pending';
    const hasLogs = message.status === 'sent' || message.status === 'failed';

    function handleCancel() {
        cancelMutation.mutate(message.id, {
            onSuccess: () => {
                toast.success(`Agendamento para ${message.contact_name} cancelado.`);
                setCancelOpen(false);
            },
            onError: () => {
                toast.error('Erro ao cancelar o agendamento. Tente novamente.');
            },
        });
    }

    return (
        <>
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="font-semibold text-sm truncate">{message.contact_name}</p>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                {message.provider_connection?.provider_label ?? 'Provider desconhecido'}{' '}
                                · Agendado para {formatDate(message.scheduled_at)}
                            </p>
                        </div>
                        <StatusBadge status={message.status} />
                    </div>
                </CardHeader>

                <CardContent className="pb-3">
                    <p className="text-sm text-muted-foreground line-clamp-2">{message.message}</p>

                    {message.attempts > 0 && (
                        <p className="text-xs text-muted-foreground mt-2">
                            {message.attempts} tentativa{message.attempts > 1 ? 's' : ''}
                        </p>
                    )}
                </CardContent>

                <CardFooter className="flex gap-2 pt-0">
                    {hasLogs && (
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex-1"
                            onClick={() => setLogsOpen(true)}
                        >
                            <ScrollText className="mr-2 size-4" />
                            Ver logs
                        </Button>
                    )}

                    {canCancel && (
                        <AlertDialog open={cancelOpen} onOpenChange={setCancelOpen}>
                            <AlertDialogTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    aria-label="Cancelar agendamento"
                                    className="text-destructive hover:text-destructive"
                                    disabled={cancelMutation.isPending}
                                >
                                    {cancelMutation.isPending ? (
                                        <Loader2 className="size-4 animate-spin" />
                                    ) : (
                                        <Trash2 className="size-4" />
                                    )}
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Cancelar agendamento</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Tem certeza que deseja cancelar o agendamento para{' '}
                                        <strong>{message.contact_name}</strong>? Esta ação não pode
                                        ser desfeita.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Voltar</AlertDialogCancel>
                                    <AlertDialogAction
                                        onClick={handleCancel}
                                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                    >
                                        Cancelar agendamento
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    )}
                </CardFooter>
            </Card>

            {logsOpen && (
                <MessageLogsDialog
                    message={logsOpen ? message : null}
                    onOpenChange={(open) => setLogsOpen(open)}
                />
            )}
        </>
    );
}
