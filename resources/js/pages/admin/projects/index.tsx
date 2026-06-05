import { Link, router, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type Project = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    start_date: string;
    end_date: string | null;
};

type Props = {
    projects: {
        data: Project[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        status: string;
    };
};

const statusLabels: Record<string, string> = {
    planning: 'Planning',
    active: 'Active',
    completed: 'Completed',
    on_hold: 'On Hold',
};

const statusColors: Record<string, string> = {
    planning:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    completed:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    on_hold: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
};

function formatDate(date: string | null): string {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleDateString();
}

export default function ProjectIndex({ projects, filters }: Props) {
    const { props } = usePage();

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        const search = formData.get('search') as string;
        const status = formData.get('status') as string;
        router.get(
            '/admin/projects',
            { search, status },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this project?')) {
            router.delete(`/admin/projects/${id}`);
        }
    };

    return (
        <AppLayout title="Projects">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Projects
                    </h2>
                    <Link
                        href="/admin/projects/create"
                        className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                    >
                        Add Project
                    </Link>
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success}
                    </div>
                )}

                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        defaultValue={filters.search}
                        placeholder="Search by name..."
                        className="flex-1 rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                    <select
                        name="status"
                        defaultValue={filters.status}
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    >
                        <option value="">All Statuses</option>
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                    <button
                        type="submit"
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                    >
                        Search
                    </button>
                </form>

                <div className="overflow-hidden rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <table className="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                        <thead className="bg-[#f5f5f4] dark:bg-[#1a1a19]">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Name
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Start Date
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    End Date
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {projects.data.map((project) => (
                                <tr key={project.id}>
                                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {project.name}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                                        <span
                                            className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${statusColors[project.status] ?? ''}`}
                                        >
                                            {statusLabels[project.status] ??
                                                project.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {formatDate(project.start_date)}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {formatDate(project.end_date)}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <Link
                                            href={`/admin/projects/${project.id}/sites`}
                                            className="mr-3 text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                                        >
                                            Sites
                                        </Link>
                                        <Link
                                            href={`/admin/projects/${project.id}/edit`}
                                            className="mr-3 text-[#f53003] hover:underline dark:text-[#FF4433]"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleDelete(project.id)
                                            }
                                            className="text-red-600 hover:underline dark:text-red-400"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {projects.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-6 py-4 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        No projects found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {projects.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Page {projects.current_page} of {projects.last_page}
                        </span>
                        <div className="flex gap-2">
                            {projects.prev_page_url && (
                                <Link
                                    href={projects.prev_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Previous
                                </Link>
                            )}
                            {projects.next_page_url && (
                                <Link
                                    href={projects.next_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
