import { useState } from 'react';
import { Outlet, NavLink, useLocation } from 'react-router-dom';
import {
    CalendarClock,
    LayoutDashboard,
    Plug,
    CalendarRange,
    Menu,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { ThemeToggle } from '@/components/shared/ThemeToggle';
import { UserMenu } from '@/components/shared/UserMenu';

interface NavItem {
    label: string;
    href: string;
    icon: React.ElementType;
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Providers', href: '/providers', icon: Plug },
    { label: 'Agendamentos', href: '/scheduled-messages', icon: CalendarRange },
];

function SidebarContent({ onNavClick }: { onNavClick?: () => void }) {
    const location = useLocation();

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-14 items-center px-4 border-b">
                <CalendarClock className="size-5 mr-2 text-primary" />
                <span className="font-semibold text-sm">Message Scheduler</span>
            </div>

            <nav className="flex-1 overflow-y-auto p-3 space-y-1">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive =
                        location.pathname === item.href ||
                        location.pathname.startsWith(item.href + '/');

                    return (
                        <NavLink
                            key={item.href}
                            to={item.href}
                            onClick={onNavClick}
                            className={cn(
                                'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                isActive
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            <Icon className="size-4 shrink-0" />
                            {item.label}
                        </NavLink>
                    );
                })}
            </nav>

            <div className="p-4 border-t">
                <p className="text-xs text-muted-foreground">v0.1.0</p>
            </div>
        </div>
    );
}

export function AppLayout() {
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="flex h-screen bg-background">
            {/* Desktop sidebar */}
            <aside className="hidden md:flex w-60 shrink-0 flex-col border-r bg-card">
                <SidebarContent />
            </aside>

            {/* Main content area */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Top header */}
                <header className="flex h-14 items-center justify-between border-b px-4 bg-card">
                    {/* Mobile menu trigger */}
                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="md:hidden">
                                {mobileOpen ? <X className="size-5" /> : <Menu className="size-5" />}
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-60 p-0">
                            <SidebarContent onNavClick={() => setMobileOpen(false)} />
                        </SheetContent>
                    </Sheet>

                    {/* Logo on mobile */}
                    <div className="flex items-center gap-2 md:hidden">
                        <CalendarClock className="size-5 text-primary" />
                        <span className="font-semibold text-sm">Message Scheduler</span>
                    </div>

                    {/* Spacer for desktop */}
                    <div className="hidden md:block" />

                    {/* Right side actions */}
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        <Separator orientation="vertical" className="h-6" />
                        <UserMenu />
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
