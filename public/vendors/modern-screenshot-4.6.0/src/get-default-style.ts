import type { Context } from './context'
import { getSandBox } from './sandbox'
import { isSVGElementNode, XMLNS } from './utils'

const ignoredStyles = [
  'width',
  'height',
  '-webkit-text-fill-color',
]

const includedAttributes = [
  'stroke',
  'fill',
]

export function getDefaultStyle(
  node: HTMLElement | SVGElement,
  pseudoElement: string | null,
  context: Context,
): Map<string, any> {
  const { defaultComputedStyles } = context

  const nodeName = node.nodeName.toLowerCase()
  const isSvgNode = isSVGElementNode(node) && nodeName !== 'svg'
  const attributes = isSvgNode
    ? includedAttributes
      .map(name => [name, node.getAttribute(name)])
      .filter(([, value]) => value !== null)
    : []

  const key = [
    isSvgNode && 'svg',
    nodeName,
    attributes.map((name, value) => `${name}=${value}`).join(','),
    pseudoElement,
  ]
    .filter(Boolean)
    .join(':')

  if (defaultComputedStyles.has(key))
    return defaultComputedStyles.get(key)!

  const sandbox = getSandBox(context)
  const sandboxWindow = sandbox?.contentWindow
  if (!sandboxWindow)
    return new Map()
  const sandboxDocument = sandboxWindow?.document

  let root: HTMLElement | SVGSVGElement
  let el: Element
  if (isSvgNode) {
    root = sandboxDocument.createElementNS(XMLNS, 'svg')
    el = root.ownerDocument.createElementNS(root.namespaceURI, nodeName)
    attributes.forEach(([name, value]) => {
      el.setAttributeNS(null, name!, value!)
    })
    root.appendChild(el)
  }
  else {
    root = el = sandboxDocument.createElement(nodeName)
  }
  el.textContent = ' '
  sandboxDocument.body.appendChild(root)
  const computedStyle = sandboxWindow.getComputedStyle(el, pseudoElement)
  const styles = new Map<string, any>()
  for (let len = computedStyle.length, i = 0; i < len; i++) {
    const name = computedStyle.item(i)
    if (ignoredStyles.includes(name))
      continue
    styles.set(name, computedStyle.getPropertyValue(name))
  }
  sandboxDocument.body.removeChild(root)

  defaultComputedStyles.set(key, styles)

  return styles
}
