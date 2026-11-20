import { cn } from "@/lib/utils";

export function RequiredBadge({ className }: { className?: string }) {
    return (
        <span className={cn("ml-1 text-destructive", className)} aria-label="Wajib diisi">
            *
        </span>
    );
}