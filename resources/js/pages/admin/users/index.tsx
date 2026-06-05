import { Link, router, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    phone: string | null;
    project: { id: number; name: string } | null;
    sub_contractor: { id: number; name: string } | null;
};

type Project = {
    id: number;
    name: string;
};

type SubContractor = {
    id: number;
    name: string;
};

type Role = {
    value: string;
    label: string;
};

type Props = {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        role: string;
        project_id: string;
        sub_contractor_id: string;
    };
    projects: Project[];
    subContractors: SubContractor[];
    roles: Role[];
};

const roleLabels: Record<string, string> = {
    system_admin: 'System Admin',
    execution_engineer: 'Execution Engineer',
    site_manager: 'Site Manager',
    permit_officer: 'Permit Officer',
    work_supervisor: 'Work Supervisor',
    hse_officer: 'HSE Officer',
    consultant: 'Consultant',
    qa_inspector: 'QA Inspector',
};

export default function UserIndex({
    users,
    filters,
    projects,
    subContractors,
    roles,
}: Props) {
    const { props } = usePage();

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.get(
            '/admin/users',
            {
                search: formData.get('search'),
                role: formData.get('role'),
                project_id: formData.get('project_id'),
                sub_contractor_id: formData.get('sub_contractor_id'),
            },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(`/admin/users/${id}`);
        }
    };

    return (
        <AppLayout title="Users">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Users
                    </h2>
                    <Link
                        href="/admin/users/create"
                        className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                    >
                        Add User
                    </Link>
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success}
                    </div>
                )}

                <form
                    onSubmit={handleSearch}
                    className="grid grid-cols-4 gap-2"
                >
                    <input
                        type="text"
                        name="search"
                        defaultValue={filters.search}
                        placeholder="Search by name or email..."
                        className="col-span-2 rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                    <select
                        name="role"
                        defaultValue={filters.role}
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    >
                        <option value="">All Roles</option>
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                    <select
                        name="project_id"
                        defaultValue={filters.project_id}
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    >
                        <option value="">All Projects</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                    <select
                        name="sub_contractor_id"
                        defaultValue={filters.sub_contractor_id}
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    >
                        <option value="">All Companies</option>
                        {subContractors.map((sc) => (
                            <option key={sc.id} value={sc.id}>
                                {sc.name}
                            </option>
                        ))}
                    </select>
                    <button
                        type="submit"
                        className="col-span-4 rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
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
                                    Email
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Role
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Project
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Company
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {users.data.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {user.name}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {user.email}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {roleLabels[user.role] ?? user.role}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {user.project?.name ?? '-'}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {user.sub_contractor?.name ??
                                            'Main Contractor'}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <Link
                                            href={`/admin/users/${user.id}/edit`}
                                            className="mr-3 text-[#f53003] hover:underline dark:text-[#FF4433]"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleDelete(user.id)
                                            }
                                            className="text-red-600 hover:underline dark:text-red-400"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-6 py-4 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {users.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Page {users.current_page} of {users.last_page}
                        </span>
                        <div className="flex gap-2">
                            {users.prev_page_url && (
                                <Link
                                    href={users.prev_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Previous
                                </Link>
                            )}
                            {users.next_page_url && (
                                <Link
                                    href={users.next_page_url}
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
