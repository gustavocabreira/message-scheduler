import { Loader2, Link as LinkIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useHuggyRedirect } from '@/hooks/useProviders';
import { navigateTo } from '@/lib/navigate';
import { toast } from 'sonner';

interface ConnectHuggyButtonProps {
    variant?: 'default' | 'outline';
    size?: 'default' | 'sm' | 'lg';
}

export function ConnectHuggyButton({ variant = 'default', size = 'default' }: ConnectHuggyButtonProps) {
    const redirectMutation = useHuggyRedirect();

    function handleConnect() {
        redirectMutation.mutate(undefined, {
            onSuccess: (data) => {
                navigateTo(data.authorization_url);
            },
            onError: () => {
                toast.error('Não foi possível iniciar a conexão com a Huggy. Tente novamente.');
            },
        });
    }

    return (
        <Button
            variant={variant}
            size={size}
            onClick={handleConnect}
            disabled={redirectMutation.isPending}
        >
            {redirectMutation.isPending ? (
                <Loader2 className="mr-2 size-4 animate-spin" />
            ) : (
                <LinkIcon className="mr-2 size-4" />
            )}
            Conectar Huggy
        </Button>
    );
}
