import { Outlet } from 'react-router-dom';
import { CalendarClock } from 'lucide-react';

export function AuthLayout() {
    return (
        <div className="min-h-screen grid lg:grid-cols-2">
            {/* Branding panel */}
            <div className="hidden lg:flex flex-col bg-neutral-900 dark:bg-neutral-950 text-white p-10 justify-between">
                <div className="flex items-center gap-2 text-lg font-semibold">
                    <CalendarClock className="size-6" />
                    <span>Message Scheduler</span>
                </div>
                <blockquote className="space-y-2">
                    <p className="text-lg leading-relaxed text-neutral-300">
                        "Agende mensagens com precisão e alcance seus contatos no momento certo,
                        de forma automática e confiável."
                    </p>
                </blockquote>
            </div>

            {/* Form panel */}
            <div className="flex items-center justify-center p-8">
                <div className="w-full max-w-sm">
                    <div className="flex items-center gap-2 mb-8 lg:hidden text-foreground">
                        <CalendarClock className="size-6" />
                        <span className="text-lg font-semibold">Message Scheduler</span>
                    </div>
                    <Outlet />
                </div>
            </div>
        </div>
    );
}
