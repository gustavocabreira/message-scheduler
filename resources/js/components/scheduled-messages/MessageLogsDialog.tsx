import { Loader2, CheckCircle2, XCircle, Clock } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useMessageLogs } from '@/hooks/useScheduledMessages';
import type { ScheduledMessage } from '@/types/scheduled-message';

interface MessageLogsDialogProps {
    message: ScheduledMessage | null;
    onOpenChange: (open: boolean) => void;
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(iso));
}

function LogStatusBadge({ status }: { status: string }) {
    if (status === 'sent') {
        return (
            <Badge variant="outline" className="border-green-500 text-green-600 dark:text-green-400">
                <CheckCircle2 className="mr-1 size-3" />
                Enviado
            </Badge>
        );
    }
    if (status === 'failed') {
        return (
            <Badge variant="outline" className="border-destructive text-destructive">
                <XCircle className="mr-1 size-3" />
                Falhou
            </Badge>
        );
    }
    return (
        <Badge variant="outline" className="text-muted-foreground">
            <Clock className="mr-1 size-3" />
            {status}
        </Badge>
    );
}

export function MessageLogsDialog({ message, onOpenChange }: MessageLogsDialogProps) {
    const { data, isLoading, isError } = useMessageLogs(message?.id ?? null);
    const logs = data?.data ?? [];

    return (
        <Dialog open={message !== null} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        Logs de entrega — {message?.contact_name}
                    </DialogTitle>
                </DialogHeader>

                {isLoading && (
                    <div className="space-y-3">
                        {[1, 2].map((i) => (
                            <div key={i} className="rounded-lg border p-3 space-y-2">
                                <div className="flex items-center justify-between">
                                    <Skeleton className="h-4 w-24" />
                                    <Skeleton className="h-5 w-16 rounded-full" />
                                </div>
                                <Skeleton className="h-3 w-full" />
                            </div>
                        ))}
                    </div>
                )}

                {isError && (
                    <div className="rounded-lg border border-destructive/50 bg-destructive/5 p-4 text-sm text-destructive">
                        <Loader2 className="inline mr-2 size-4 animate-spin" />
                        Erro ao carregar os logs. Tente novamente.
                    </div>
                )}

                {!isLoading && !isError && logs.length === 0 && (
                    <p className="text-sm text-muted-foreground text-center py-6">
                        Nenhum log de entrega encontrado.
                    </p>
                )}

                {!isLoading && !isError && logs.length > 0 && (
                    <div className="space-y-3 max-h-96 overflow-y-auto">
                        {logs.map((log) => (
                            <div key={log.id} className="rounded-lg border p-3 space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        Tentativa {log.attempt}
                                    </span>
                                    <LogStatusBadge status={log.status} />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {formatDate(log.created_at)}
                                </p>
                                {log.error_message && (
                                    <p className="text-xs text-destructive bg-destructive/5 rounded p-2">
                                        {log.error_message}
                                    </p>
                                )}
                                {log.response && (
                                    <p className="text-xs text-muted-foreground bg-muted rounded p-2 break-all">
                                        {log.response}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
