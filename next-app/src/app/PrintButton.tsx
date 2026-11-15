"use client";

export default function PrintButton({ children, className = "button button-primary" }: { children: string; className?: string }) {
  return <button className={className} type="button" onClick={() => window.print()}>{children}</button>;
}
