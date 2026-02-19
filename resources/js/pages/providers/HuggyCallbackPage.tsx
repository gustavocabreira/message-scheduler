import { useEffect, useRef } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { Loader2, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useHuggyCallback } from '@/hooks/useProviders';

export function HuggyCallbackPage() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const code = searchParams.get('code');
    const calledRef = useRef(false);

    const callbackMutation = useHuggyCallback();

    useEffect(() => {
        if (!code || calledRef.current) return;
        calledRef.current = true;

        callbackMutation.mutate(code, {
            onSuccess: () => {
                navigate('/providers', { replace: true });
            },
        });
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [code]);

    if (!code) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <AlertCircle className="size-12 text-destructive mb-4" />
                <h1 className="text-xl font-semibold mb-2">Código de autorização ausente</h1>
                <p className="text-sm text-muted-foreground mb-6 max-w-xs">
                    O código de autorização da Huggy não foi recebido. O fluxo OAuth pode ter sido
                    cancelado ou houve um erro na Huggy.
                </p>
                <Button asChild variant="outline">
                    <Link to="/providers">Voltar para Providers</Link>
                </Button>
            </div>
        );
    }

    if (callbackMutation.isError) {
        const errorMessage =
            (callbackMutation.error as { response?: { data?: { message?: string } } })?.response
                ?.data?.message ?? 'Ocorreu um erro inesperado.';

        return (
            <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <AlertCircle className="size-12 text-destructive mb-4" />
                <h1 className="text-xl font-semibold mb-2">Falha ao conectar com a Huggy</h1>
                <p className="text-sm text-muted-foreground mb-2 max-w-xs">{errorMessage}</p>
                <p className="text-xs text-muted-foreground mb-6">
                    Verifique suas configurações e tente novamente.
                </p>
                <Button asChild variant="outline">
                    <Link to="/providers">Voltar para Providers</Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <Loader2 className="size-10 animate-spin text-primary mb-4" />
            <h1 className="text-xl font-semibold mb-2">Conectando com a Huggy…</h1>
            <p className="text-sm text-muted-foreground">
                Aguarde enquanto finalizamos a autenticação.
            </p>
        </div>
    );
}
