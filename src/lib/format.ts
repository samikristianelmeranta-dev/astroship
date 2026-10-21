const dateFmt = new Intl.DateTimeFormat("fi-FI", { day: "numeric", month: "long", year: "numeric" });

export const formatDate = (d: Date) => dateFmt.format(d);

export const readingTime = (text: string) => Math.max(1, Math.round(text.split(/\s+/).length / 180));
