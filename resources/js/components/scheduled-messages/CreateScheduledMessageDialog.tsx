import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCreateScheduledMessage } from '@/hooks/useScheduledMessages';
import { useProviders } from '@/hooks/useProviders';

const createSchema = z.object({
    provider_connection_id: z.string().min(1, 'Selecione um provider.'),
    contact_id: z.string().min(1, 'Informe o ID do contato.'),
    contact_name: z.string().min(1, 'Informe o nome do contato.'),
    message: z.string().min(1, 'Informe a mensagem.'),
    scheduled_at: z.string().min(1, 'Informe a data e hora de envio.'),
});

type FormValues = z.infer<typeof createSchema>;

interface CreateScheduledMessageDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CreateScheduledMessageDialog({
    open,
    onOpenChange,
}: CreateScheduledMessageDialogProps) {
    const { data: providersData } = useProviders();
    const providers = providersData?.data ?? [];
    const createMutation = useCreateScheduledMessage();

    const form = useForm<FormValues>({
        resolver: zodResolver(createSchema),
        defaultValues: {
            provider_connection_id: '',
            contact_id: '',
            contact_name: '',
            message: '',
            scheduled_at: '',
        },
    });

    function handleClose(open: boolean) {
        if (!open) {
            form.reset();
        }
        onOpenChange(open);
    }

    function onSubmit(values: FormValues) {
        createMutation.mutate(
            {
                provider_connection_id: Number(values.provider_connection_id),
                contact_id: values.contact_id,
                contact_name: values.contact_name,
                message: values.message,
                scheduled_at: new Date(values.scheduled_at).toISOString(),
            },
            {
                onSuccess: () => {
                    toast.success('Mensagem agendada com sucesso.');
                    handleClose(false);
                },
                onError: (error: { response?: { data?: { message?: string } } }) => {
                    const message =
                        error.response?.data?.message ?? 'Erro ao agendar a mensagem.';
                    form.setError('root', { message });
                },
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Novo agendamento</DialogTitle>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(onSubmit)}
                        className="space-y-4"
                        noValidate
                    >
                        <FormField
                            control={form.control}
                            name="provider_connection_id"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Provider</FormLabel>
                                    <Select
                                        value={field.value}
                                        onValueChange={field.onChange}
                                        disabled={createMutation.isPending}
                                    >
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecione um provider" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            {providers.length === 0 && (
                                                <SelectItem value="_none" disabled>
                                                    Nenhum provider conectado
                                                </SelectItem>
                                            )}
                                            {providers.map((p) => (
                                                <SelectItem key={p.id} value={String(p.id)}>
                                                    {p.provider_label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="contact_id"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>ID do contato</FormLabel>
                                    <FormControl>
                                        <Input
                                            placeholder="Ex: 123456"
                                            disabled={createMutation.isPending}
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="contact_name"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Nome do contato</FormLabel>
                                    <FormControl>
                                        <Input
                                            placeholder="Ex: João Silva"
                                            disabled={createMutation.isPending}
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="message"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Mensagem</FormLabel>
                                    <FormControl>
                                        <Textarea
                                            placeholder="Digite a mensagem a ser enviada..."
                                            rows={3}
                                            disabled={createMutation.isPending}
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="scheduled_at"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Data e hora de envio</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="datetime-local"
                                            disabled={createMutation.isPending}
                                            {...field}
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        {form.formState.errors.root && (
                            <p className="text-sm text-destructive">
                                {form.formState.errors.root.message}
                            </p>
                        )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => handleClose(false)}
                                disabled={createMutation.isPending}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={createMutation.isPending}>
                                {createMutation.isPending && (
                                    <Loader2 className="mr-2 size-4 animate-spin" />
                                )}
                                Agendar
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
