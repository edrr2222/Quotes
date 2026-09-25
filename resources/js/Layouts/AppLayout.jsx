import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="bg-[#1F3864] text-white">
                <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                    <Link href={route('dashboard')} className="font-semibold text-lg">
                        Cotizador de Construcción
                    </Link>
                    <div className="flex items-center gap-4 text-sm">
                        <span className="text-white/70">{auth?.user?.name}</span>
                        <Link href={route('profile.edit')} className="hover:underline">Perfil</Link>
                        <Link href={route('logout')} method="post" as="button" className="hover:underline">
                            Salir
                        </Link>
                    </div>
                </div>
            </nav>
            <main className="max-w-6xl mx-auto px-4 py-8">{children}</main>
        </div>
    );
}
