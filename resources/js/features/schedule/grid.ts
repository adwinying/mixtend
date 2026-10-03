/** 1時間分の行の高さ（px）。グリッド線を含む */
export const HOUR_HEIGHT_PX = 110;
/** グリッド線の太さ（px）。ブロックが次の行の線に重ならないよう高さから引く */
export const GRID_LINE_PX = 2;

export const toMinutes = (time: string) => {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
};
