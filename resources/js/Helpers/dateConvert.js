import moment from "moment";
export function dateConvert(date, format = "DD-MM-YYYY, HH:mm:ss") {
    const dayConvert = {
        Sunday: "Minggu",
        Monday: "Senin",
        Tuesday: "Selasa",
        Wednesday: "Rabu",
        Thursday: "Kamis",
        Friday: "Jumat",
        Saturday: "Sabtu",
    };
    const dayName = moment(date).format("dddd");
    const dateFormat = moment(date).format(format);
    return dayConvert[dayName] + ", " + dateFormat ?? dayName;
}
