import gsap from "gsap";
import Textify from "textify.js";
import FontFaceObserver from "fontfaceobserver";

// TODO: This can be further optimized

const cairo = new FontFaceObserver("cairo");
const inter = new FontFaceObserver("inter");
const arefRuqaa = new FontFaceObserver("aref ruqaa");
const zain = new FontFaceObserver("zain");

Promise.all([cairo.load(), inter.load(), arefRuqaa.load(), zain.load()]).then(
    () => {
        const any = document.querySelector("[data-textify]");
        if (!any) return;

        new Textify(
            {
                splitType: "lines",
                largeText: true,
                animation: {
                    by: "lines",
                    ease: "power2",
                    stagger: 0.075,
                },
            },
            gsap,
        );
    },
);
