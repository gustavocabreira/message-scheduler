import { CalendarRange, Plug, Send } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAuthStore } from '@/stores/auth.store';

interface StatCard {
    title: string;
    description: string;
    icon: React.ElementType;
    value: string;
}

const stats: StatCard[] = [
    {
        title: 'Agendamentos',
        description: 'Total de mensagens agendadas',
        icon: CalendarRange,
        value: '—',
    },
    {
        title: 'Providers',
        description: 'Integrações conectadas',
        icon: Plug,
        value: '—',
    },
    {
        title: 'Enviadas',
        description: 'Mensagens enviadas com sucesso',
        icon: Send,
        value: '—',
    },
];

export function DashboardPage() {
    const user = useAuthStore((s) => s.user);

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">
                    Olá, {user?.name?.split(' ')[0] ?? 'usuário'} 👋
                </h1>
                <p className="text-muted-foreground text-sm mt-1">
                    Bem-vindo ao painel do Message Scheduler.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {stats.map((stat) => {
                    const Icon = stat.icon;
                    return (
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                                <Icon className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <CardDescription className="text-xs mt-1">
                                    {stat.description}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </div>
    );
}
