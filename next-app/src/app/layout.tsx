import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Bank Sampah Digital",
  description: "Sistem informasi pengelolaan bank sampah RT/RW.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="id">
      <body>{children}</body>
    </html>
  );
}
