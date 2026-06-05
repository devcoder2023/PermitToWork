import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { User } from '@/types/auth';

type Props = {
    title?: string;
    children: ReactNode;
};

const roleLabels: Record<User['role'], string> = {
    system_admin: 'System Admin',
    execution_engineer: 'Execution Engineer',
    site_manager: 'Site Manager',
    permit_officer: 'Permit Officer',
    work_supervisor: 'Work Supervisor',
    hse_officer: 'HSE Officer',
    consultant: 'Consultant',
    qa_inspector: 'QA Inspector',
};

export default function AppLayout({ title, children }: Props) {
    const { auth } = usePage().props;
    const user = auth?.user;

    if (!user) {
        return null;
    }

    const isAdmin = user.role === 'system_admin';

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <>
            <Head title={title}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <aside className="flex w-64 flex-col border-r border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="border-b border-[#e3e3e0] p-4 dark:border-[#3E3E3A]">
                        <Link
                            href="/dashboard"
                            className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            PTW System
                        </Link>
                    </div>

                    <nav className="flex-1 p-4">
                        <ul className="space-y-1">
                            <li>
                                <Link
                                    href="/dashboard"
                                    className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                >
                                    Dashboard
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/permits"
                                    className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                >
                                    Permits
                                </Link>
                            </li>
                        </ul>

                        {isAdmin && (
                            <>
                                <p className="mt-6 mb-2 text-xs font-semibold tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Administration
                                </p>
                                <ul className="space-y-1">
                                    <li>
                                        <Link
                                            href="/admin/sub-contractors"
                                            className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                        >
                                            Sub-Contractors
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href="/admin/projects"
                                            className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                        >
                                            Projects
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href="/admin/users"
                                            className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                        >
                                            Users
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href="/admin/permit-types"
                                            className="block rounded-md px-3 py-2 text-sm text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#1a1a19] dark:hover:text-[#EDEDEC]"
                                        >
                                            Permit Types
                                        </Link>
                                    </li>
                                </ul>
                            </>
                        )}
                    </nav>

                    <div className="border-t border-[#e3e3e0] p-4 dark:border-[#3E3E3A]">
                        <div className="mb-2">
                            <p className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                {user.name}
                            </p>
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                {roleLabels[user.role]}
                            </p>
                        </div>
                    </div>
                </aside>

                <div className="flex flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-[#e3e3e0] bg-white px-6 py-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h1 className="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            {title ?? 'Dashboard'}
                        </h1>

                        <button
                            onClick={handleLogout}
                            type="button"
                            className="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                        >
                            Sign out
                        </button>
                    </header>

                    <main className="flex-1 p-6">{children}</main>
                </div>
            </div>
        </>
    );
}
