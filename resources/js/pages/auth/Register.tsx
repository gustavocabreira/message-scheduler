import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import api, { initCsrf } from '@/lib/api';
import { useAuthStore, type AuthUser } from '@/stores/auth.store';

const registerSchema = z
    .object({
        name: z.string().min(2, 'O nome deve ter pelo menos 2 caracteres.'),
        email: z.string().email('Informe um e-mail válido.'),
        password: z.string().min(8, 'A senha deve ter pelo menos 8 caracteres.'),
        password_confirmation: z.string().min(1, 'Confirme sua senha.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'As senhas não coincidem.',
        path: ['password_confirmation'],
    });

type RegisterFormValues = z.infer<typeof registerSchema>;

interface RegisterResponse {
    data: AuthUser;
}

export function RegisterPage() {
    const navigate = useNavigate();
    const setUser = useAuthStore((s) => s.setUser);

    const form = useForm<RegisterFormValues>({
        resolver: zodResolver(registerSchema),
        defaultValues: { name: '', email: '', password: '', password_confirmation: '' },
    });

    const registerMutation = useMutation({
        mutationFn: async (values: RegisterFormValues) => {
            await initCsrf();
            const response = await api.post<RegisterResponse>('/auth/register', values);
            return response.data;
        },
        onSuccess: (data) => {
            setUser(data.data);
            navigate('/dashboard', { replace: true });
        },
        onError: (error: { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }) => {
            const responseData = error.response?.data;

            if (responseData?.errors) {
                Object.entries(responseData.errors).forEach(([field, messages]) => {
                    form.setError(field as keyof RegisterFormValues, {
                        message: messages[0],
                    });
                });
            } else {
                form.setError('root', {
                    message: responseData?.message ?? 'Erro ao criar conta. Tente novamente.',
                });
            }
        },
    });

    function onSubmit(values: RegisterFormValues) {
        registerMutation.mutate(values);
    }

    return (
        <div className="space-y-6">
            <div className="space-y-2">
                <h1 className="text-2xl font-bold tracking-tight">Criar conta</h1>
                <p className="text-sm text-muted-foreground">
                    Preencha os dados abaixo para criar sua conta.
                </p>
            </div>

            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4" noValidate>
                    <FormField
                        control={form.control}
                        name="name"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Nome</FormLabel>
                                <FormControl>
                                    <Input
                                        placeholder="Seu nome completo"
                                        autoComplete="name"
                                        disabled={registerMutation.isPending}
                                        {...field}
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="email"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>E-mail</FormLabel>
                                <FormControl>
                                    <Input
                                        type="email"
                                        placeholder="voce@exemplo.com"
                                        autoComplete="email"
                                        disabled={registerMutation.isPending}
                                        {...field}
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="password"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Senha</FormLabel>
                                <FormControl>
                                    <Input
                                        type="password"
                                        placeholder="Mínimo 8 caracteres"
                                        autoComplete="new-password"
                                        disabled={registerMutation.isPending}
                                        {...field}
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="password_confirmation"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel>Confirmar senha</FormLabel>
                                <FormControl>
                                    <Input
                                        type="password"
                                        placeholder="Repita a senha"
                                        autoComplete="new-password"
                                        disabled={registerMutation.isPending}
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

                    <Button type="submit" className="w-full" disabled={registerMutation.isPending}>
                        {registerMutation.isPending && (
                            <Loader2 className="mr-2 size-4 animate-spin" />
                        )}
                        Criar conta
                    </Button>
                </form>
            </Form>

            <p className="text-center text-sm text-muted-foreground">
                Já tem uma conta?{' '}
                <Link to="/login" className="font-medium text-primary hover:underline">
                    Faça login
                </Link>
            </p>
        </div>
    );
}
