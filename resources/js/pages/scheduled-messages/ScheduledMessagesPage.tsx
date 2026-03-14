import { useState, useCallback } from 'react';
import { CalendarPlus, CalendarX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { ScheduledMessageCard } from '@/components/scheduled-messages/ScheduledMessageCard';
import { CreateScheduledMessageDialog } from '@/components/scheduled-messages/CreateScheduledMessageDialog';
import { useScheduledMessages } from '@/hooks/useScheduledMessages';
import type { ScheduledMessageStatus, ScheduledMessagesFilters } from '@/types/scheduled-message';

const STATUS_FILTERS: { label: string; value: ScheduledMessageStatus | undefined }[] = [
    { label: 'Todos', value: undefined },
    { label: 'Pendentes', value: 'pending' },
    { label: 'Enviados', value: 'sent' },
    { label: 'Falhou', value: 'failed' },
    { label: 'Cancelados', value: 'cancelled' },
];

function ScheduledMessageSkeleton() {
    return (
        <div className="rounded-lg border p-4 space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="space-y-1.5">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-3 w-48" />
                </div>
                <Skeleton className="h-5 w-20 rounded-full" />
            </div>
            <Skeleton className="h-3 w-full" />
            <Skeleton className="h-3 w-3/4" />
            <div className="flex gap-2 pt-1">
                <Skeleton className="h-8 flex-1" />
            </div>
        </div>
    );
}

function EmptyState({ onCreateClick }: { onCreateClick: () => void }) {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <div className="size-16 rounded-full bg-muted flex items-center justify-center mb-4">
                <CalendarX className="size-8 text-muted-foreground" />
            </div>
            <h2 className="text-lg font-semibold mb-1">Nenhum agendamento encontrado</h2>
            <p className="text-sm text-muted-foreground max-w-xs mb-6">
                Crie seu primeiro agendamento para começar a enviar mensagens programadas.
            </p>
            <Button onClick={onCreateClick}>
                <CalendarPlus className="mr-2 size-4" />
                Novo agendamento
            </Button>
        </div>
    );
}

export function ScheduledMessagesPage() {
    const [createOpen, setCreateOpen] = useState(false);
    const [filters, setFilters] = useState<ScheduledMessagesFilters>({});
    const [contactSearch, setContactSearch] = useState('');

    const { data, isLoading, isError } = useScheduledMessages(filters);
    const messages = data?.data ?? [];
    const meta = data?.meta;

    const handleStatusFilter = useCallback(
        (status: ScheduledMessageStatus | undefined) => {
            setFilters((prev) => ({ ...prev, status, page: 1 }));
        },
        [],
    );

    const handleContactSearch = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const value = e.target.value;
            setContactSearch(value);
            setFilters((prev) => ({
                ...prev,
                contact_name: value || undefined,
                page: 1,
            }));
        },
        [],
    );

    const handlePageChange = useCallback((page: number) => {
        setFilters((prev) => ({ ...prev, page }));
    }, []);

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Agendamentos</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Gerencie suas mensagens agendadas.
                    </p>
                </div>
                {!isLoading && (messages.length > 0 || Object.keys(filters).some(Boolean)) && (
                    <Button size="sm" onClick={() => setCreateOpen(true)}>
                        <CalendarPlus className="mr-2 size-4" />
                        Novo agendamento
                    </Button>
                )}
            </div>

            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-3">
                <Input
                    placeholder="Buscar por contato..."
                    value={contactSearch}
                    onChange={handleContactSearch}
                    className="sm:max-w-xs"
                />
                <div className="flex flex-wrap gap-1.5">
                    {STATUS_FILTERS.map(({ label, value }) => (
                        <Button
                            key={label}
                            variant={filters.status === value ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => handleStatusFilter(value)}
                        >
                            {label}
                        </Button>
                    ))}
                </div>
            </div>

            {/* Error */}
            {isError && (
                <div className="rounded-lg border border-destructive/50 bg-destructive/5 p-4 text-sm text-destructive">
                    Erro ao carregar os agendamentos. Tente recarregar a página.
                </div>
            )}

            {/* Loading */}
            {isLoading && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <ScheduledMessageSkeleton />
                    <ScheduledMessageSkeleton />
                    <ScheduledMessageSkeleton />
                </div>
            )}

            {/* Empty */}
            {!isLoading && !isError && messages.length === 0 && (
                <EmptyState onCreateClick={() => setCreateOpen(true)} />
            )}

            {/* Message list */}
            {!isLoading && !isError && messages.length > 0 && (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {messages.map((msg) => (
                        <ScheduledMessageCard key={msg.id} message={msg} />
                    ))}
                </div>
            )}

            {/* Pagination */}
            {meta && meta.last_page > 1 && (
                <div className="flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={meta.current_page <= 1}
                        onClick={() => handlePageChange(meta.current_page - 1)}
                    >
                        Anterior
                    </Button>
                    <span className="text-sm text-muted-foreground">
                        Página {meta.current_page} de {meta.last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={meta.current_page >= meta.last_page}
                        onClick={() => handlePageChange(meta.current_page + 1)}
                    >
                        Próxima
                    </Button>
                </div>
            )}

            {/* Create Dialog — only mounted when open to avoid spurious API calls */}
            {createOpen && (
                <CreateScheduledMessageDialog
                    open={createOpen}
                    onOpenChange={setCreateOpen}
                />
            )}
        </div>
    );
}
