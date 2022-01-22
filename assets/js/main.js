window.addEventListener('scroll', () => {
    const backtoTop = document.querySelector('#back-to-top')
    backtoTop.classList.toggle('display-none', window.scrollY > 0)
})

const backtoTop = document.querySelector('#back-to-top')
backtoTop.addEventListener('click', () => {
    console.log('Hello')
    window.scrollTo(0, 0)
})