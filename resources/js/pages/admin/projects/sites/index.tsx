import { Link, router, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type Project = {
    id: number;
    name: string;
};

type Site = {
    id: number;
    name: string;
    description: string | null;
};

type Props = {
    project: Project;
    sites: {
        data: Site[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
    };
};

export default function SiteIndex({ project, sites, filters }: Props) {
    const { props } = usePage();

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        const search = formData.get('search') as string;
        router.get(
            `/admin/projects/${project.id}/sites`,
            { search },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this site?')) {
            router.delete(`/admin/sites/${id}`);
        }
    };

    return (
        <AppLayout title={`Sites - ${project.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href="/admin/projects"
                            className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            &larr; Back to Projects
                        </Link>
                        <h2 className="mt-2 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Sites for {project.name}
                        </h2>
                    </div>
                    <Link
                        href={`/admin/projects/${project.id}/sites/create`}
                        className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                    >
                        Add Site
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
                                    Description
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {sites.data.map((site) => (
                                <tr key={site.id}>
                                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {site.name}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        {site.description || '-'}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <Link
                                            href={`/admin/sites/${site.id}/edit`}
                                            className="mr-3 text-[#f53003] hover:underline dark:text-[#FF4433]"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleDelete(site.id)
                                            }
                                            className="text-red-600 hover:underline dark:text-red-400"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {sites.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={3}
                                        className="px-6 py-4 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        No sites found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {sites.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Page {sites.current_page} of {sites.last_page}
                        </span>
                        <div className="flex gap-2">
                            {sites.prev_page_url && (
                                <Link
                                    href={sites.prev_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Previous
                                </Link>
                            )}
                            {sites.next_page_url && (
                                <Link
                                    href={sites.next_page_url}
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
