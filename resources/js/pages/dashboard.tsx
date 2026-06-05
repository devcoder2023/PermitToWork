import AppLayout from '@/components/layouts/app-layout';

export default function Dashboard() {
    return (
        <AppLayout title="Dashboard">
            <div className="mb-8">
                <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    Welcome to the Permit To Work system
                </h2>
                <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Manage your permits, approvals, and observations from this
                    dashboard.
                </p>
            </div>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div className="rounded-lg bg-white p-6 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h3 className="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Active Permits
                    </h3>
                    <p className="mt-2 text-3xl font-bold text-[#f53003] dark:text-[#FF4433]">
                        0
                    </p>
                </div>

                <div className="rounded-lg bg-white p-6 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h3 className="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Pending Approvals
                    </h3>
                    <p className="mt-2 text-3xl font-bold text-[#f53003] dark:text-[#FF4433]">
                        0
                    </p>
                </div>

                <div className="rounded-lg bg-white p-6 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <h3 className="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Open Observations
                    </h3>
                    <p className="mt-2 text-3xl font-bold text-[#f53003] dark:text-[#FF4433]">
                        0
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
